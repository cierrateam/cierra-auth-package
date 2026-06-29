<?php

namespace Cierra\Auth\Controllers;

use App\Models\User;
use Cierra\Auth\Auth;
use Cierra\Auth\Contracts\TeamResolver;
use Cierra\Auth\Services\ContextService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * HTTP timeout (seconds) for talking to admin.cierra.ai.
     * Kept short so a stuck SSO server can't pin the worker.
     */
    private const SSO_HTTP_TIMEOUT = 10;

    public function login()
    {
        return redirect(Auth::getLoginUrl());
    }

    public function callback()
    {
        $code = request()->code;
        $stateFromRequest = request()->state;

        if (! $code) {
            // Provider returned an error or user cancelled. Surface the OAuth
            // error message (if any) in the log for debugging.
            if ($err = request()->error) {
                Log::info('[cierra-auth] OAuth callback returned error', [
                    'error' => $err,
                    'description' => request()->error_description,
                ]);
            }

            return $this->loginRedirectWithError(__('Sign-in was cancelled. Please try again.'));
        }

        // CSRF state validation — single-use, constant-time compare.
        // We always pull the session state (so a missing one can't be replayed),
        // but only ENFORCE the check when the upstream sent a state back. This
        // keeps backward compatibility with admin.cierra.ai instances that may
        // not yet echo `state` in the redirect; once the server is confirmed to
        // round-trip state (it does via League OAuth2 server), this guard can be
        // tightened to always-required.
        $stateFromSession = Auth::pullState();

        if ($stateFromRequest !== null && $stateFromRequest !== '') {
            if (! is_string($stateFromSession) || $stateFromSession === ''
                || ! hash_equals($stateFromSession, (string) $stateFromRequest)) {
                Log::warning('[cierra-auth] OAuth state mismatch — possible CSRF or stale session', [
                    'has_session_state' => is_string($stateFromSession) && $stateFromSession !== '',
                    'request_state_len' => strlen((string) $stateFromRequest),
                ]);

                return $this->loginRedirectWithError(__('Your sign-in session expired. Please try again.'));
            }
        }

        try {
            $tokenRes = Http::timeout(self::SSO_HTTP_TIMEOUT)
                ->asForm()
                ->post(config('cierra-auth-package.host').'/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'client_id' => config('cierra-auth-package.client_id'),
                    'client_secret' => config('cierra-auth-package.client_secret'),
                    'redirect_uri' => route('cierra-auth.callback'),
                    'code' => $code,
                ]);
        } catch (ConnectionException $e) {
            Log::error('[cierra-auth] OAuth token endpoint unreachable', ['message' => $e->getMessage()]);

            return $this->loginRedirectWithError(__('The sign-in service is temporarily unavailable. Please try again in a moment.'));
        } catch (\Throwable $e) {
            Log::error('[cierra-auth] OAuth token exchange failed unexpectedly', ['message' => $e->getMessage()]);

            return $this->loginRedirectWithError(__('Sign-in failed. Please try again.'));
        }

        if (! $tokenRes->ok()) {
            Log::info('[cierra-auth] OAuth token exchange returned non-2xx', [
                'status' => $tokenRes->status(),
                'body' => Str::limit((string) $tokenRes->body(), 500),
            ]);

            return $this->loginRedirectWithError(__('Your sign-in link expired. Please try again.'));
        }

        $tokenJson = $tokenRes->json();

        if (! is_array($tokenJson) || empty($tokenJson['access_token'])) {
            return $this->loginRedirectWithError(__('Sign-in failed. Please try again.'));
        }

        $token = $tokenJson['access_token'];

        // Fetch the user info — same timeout + same graceful handling.
        try {
            $response = Http::timeout(self::SSO_HTTP_TIMEOUT)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ])->get(config('cierra-auth-package.host').'/api/user');
        } catch (\Throwable $e) {
            Log::error('[cierra-auth] /api/user fetch failed', ['message' => $e->getMessage()]);

            return $this->loginRedirectWithError(__('The sign-in service is temporarily unavailable. Please try again in a moment.'));
        }

        if (! $response->ok()) {
            Log::warning('[cierra-auth] /api/user returned non-2xx', [
                'status' => $response->status(),
                'body' => Str::limit((string) $response->body(), 500),
            ]);

            return $this->loginRedirectWithError(__('Sign-in failed. Please try again.'));
        }

        $passportUser = $response->json();

        if (! is_array($passportUser) || empty($passportUser['email'])) {
            Log::warning('[cierra-auth] /api/user payload missing email', [
                'keys' => is_array($passportUser) ? array_keys($passportUser) : 'not_array',
            ]);

            return $this->loginRedirectWithError(__('Sign-in failed. Please try again.'));
        }

        // Register app in admin panel — fire-and-forget, but never block login.
        if (config('cierra-auth-package.registers_app_id')) {
            try {
                Http::timeout(self::SSO_HTTP_TIMEOUT)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$token,
                        'Accept' => 'application/json',
                    ])->post(config('cierra-auth-package.host').'/api/app/register', [
                        'application_id' => config('cierra-auth-package.registers_app_id'),
                    ]);
            } catch (\Throwable $e) {
                Log::warning('[cierra-auth] app/register call failed (non-fatal)', ['message' => $e->getMessage()]);
            }
        }

        $userData = [
            'cierra_auth_id' => $passportUser['id'] ?? null,
            'first_name' => $passportUser['first_name'] ?? '',
            'last_name' => $passportUser['last_name'] ?? '',
            'token' => $token,
            'refresh_token' => $tokenJson['refresh_token'] ?? null,
            'token_expires_in' => isset($tokenJson['expires_in']) ? now()->addSeconds((int) $tokenJson['expires_in']) : null,
            'email_verified_at' => $passportUser['email_verified_at'] ?? null,
            'cierra_auth_team_id' => $passportUser['current_team_id'] ?? null,
        ];

        if (Schema::hasColumn('users', 'name')) {
            $userData['name'] = trim(($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''));
        }

        // If there is a password field, fill it with random string so that
        // local-login attempts against an SSO-provisioned user always fail.
        if (Schema::hasColumn('users', 'password')) {
            $userData['password'] = bcrypt(Str::random(40));
        }

        $user = User::updateOrCreate(
            [
                'email' => $passportUser['email'],
            ],
            $userData
        );

        $user = app(TeamResolver::class)->resolve($user);

        // Regenerate session id on login to neutralise session fixation.
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        FacadesAuth::guard('web')->login($user, true);

        // Warm license context cache (fail-open: don't block login on context fetch failure)
        try {
            app(ContextService::class)->forUser($user);
        } catch (\Throwable $e) {
            Log::warning('[cierra-auth] failed to warm license context after login: '.$e->getMessage());
        }

        return redirect(config('cierra-auth-package.redirect_after_login'));
    }

    public function logout()
    {
        FacadesAuth::logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        // config('cierra-auth-package.redirect_after_logout') is the url to redirect after logout, it may be path or domain+path, the final result should be a valid url like domain+path
        $redirectAfterLogout = config('cierra-auth-package.redirect_after_logout');

        // check if includes domain
        if (strpos($redirectAfterLogout, 'http') === false) {
            // if not, add domain
            $redirectAfterLogout = implode('/', [config('app.url'), ltrim((string) $redirectAfterLogout, '/')]);
        }

        // remove trailing slashes
        $redirectAfterLogout = rtrim($redirectAfterLogout, '/');

        return redirect(config('cierra-auth-package.host').'/logout?redirect_after_logout='.urlencode($redirectAfterLogout));
    }

    /**
     * Redirect back to the local login bootstrap with a flashed error message.
     * Using `with('error', ...)` matches what host apps already render in their
     * login views; falls back gracefully if they don't.
     */
    private function loginRedirectWithError(string $message)
    {
        return redirect()->route('cierra-auth.login')->with('error', $message);
    }
}
