<?php

namespace Cierra\Auth;

use Cierra\Auth\Services\ContextService;
use Illuminate\Database\Eloquent\Model;
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

        // Reflect the saved values onto the local user record so the UI is
        // consistent right away. Only when the authenticated user is an
        // Eloquent model carrying the columns — in stateless/API contexts the
        // user may be a GenericUser (no DB row), and the remote write is the
        // source of truth, so we simply skip the local mirror.
        //
        // We issue a targeted keyed UPDATE of just these columns (rather than
        // forceFill()->save() on the shared instance) so we neither persist nor
        // roll back any unrelated in-memory edits the caller may hold, and only
        // sync the in-memory values once the write actually succeeds.
        if ($user instanceof Model) {
            $table = $user->getTable();
            $local = [];

            if (Schema::hasColumn($table, 'mail_signature')) {
                $local['mail_signature'] = $signature;
            }

            if ($position !== null && Schema::hasColumn($table, 'position')) {
                $local['position'] = $position;
            }

            if ($local !== []) {
                try {
                    $user->newQuery()->whereKey($user->getKey())->update($local);
                    $user->forceFill($local);
                } catch (\Throwable $e) {
                    logger()->warning('[cierra-auth] failed to mirror mail signature locally: '.$e->getMessage());
                }
            }
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
