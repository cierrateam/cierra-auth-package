<?php

namespace Cierra\Auth;

use Cierra\Auth\Services\ContextService;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class Auth
{
    public static function getLoginUrl()
    {
        $queryParams = [
            'client_id' => config('cierra-auth-package.client_id'),
            'redirect_uri' => route('cierra-auth.callback'),
            'response_type' => 'code',
            'scope' => '*',
        ];

        return config('cierra-auth-package.host').'/oauth/authorize?'.http_build_query($queryParams);
    }

    // get user function
    public static function user()
    {
        return FacadesAuth::user();
    }

    /**
     * Persist the authenticated user's default mail signature (and optionally
     * their job title) back to the central admin.cierra.ai profile, so it
     * becomes the single source of truth and syncs out to every other app.
     *
     * Mirrors the change onto the local user record when the columns exist,
     * so the change is reflected immediately without waiting for a re-login.
     *
     * @return bool whether the remote profile was updated successfully
     */
    public static function updateMailSignature(string $signature, ?string $position = null): bool
    {
        $user = FacadesAuth::user();

        if (! $user || empty($user->token)) {
            return false;
        }

        $payload = ['mail_signature' => $signature];

        if ($position !== null) {
            $payload['position'] = $position;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$user->token,
            'Accept' => 'application/json',
        ])->put(config('cierra-auth-package.host').'/api/me/mail-signature', $payload);

        if (! $response->successful()) {
            logger()->warning('[cierra-auth] failed to update mail signature: HTTP '.$response->status());

            return false;
        }

        // Reflect the saved values locally so the UI is consistent right away.
        $local = [];

        if (Schema::hasColumn('users', 'mail_signature')) {
            $local['mail_signature'] = $signature;
        }

        if ($position !== null && Schema::hasColumn('users', 'position')) {
            $local['position'] = $position;
        }

        if (! empty($local)) {
            $user->forceFill($local)->save();
        }

        // The central profile changed; drop any cached context for this user.
        try {
            app(ContextService::class)->flush($user);
        } catch (\Throwable $e) {
            // non-fatal
        }

        return true;
    }
}
