<?php

namespace Cierra\Auth;

use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Str;

class Auth
{
    /**
     * Build the OAuth authorize URL and store a fresh CSRF `state` value in the session.
     *
     * The state is then validated in AuthController::callback() (constant-time compare,
     * single-use). This is defence-in-depth against cross-site OAuth callback injection;
     * the OAuth server (admin.cierra.ai) also exact-matches redirect_uri so the attack
     * surface is limited, but the OAuth 2.0 spec REQUIRES state for all redirect-based
     * flows. See RFC 6749 §10.12.
     */
    public static function getLoginUrl()
    {
        $state = self::generateState();

        $queryParams = [
            'client_id' => config('cierra-auth-package.client_id'),
            'redirect_uri' => route('cierra-auth.callback'),
            'response_type' => 'code',
            'scope' => '*',
            'state' => $state,
        ];

        return config('cierra-auth-package.host').'/oauth/authorize?'.http_build_query($queryParams);
    }

    /**
     * Generate a cryptographically secure single-use OAuth `state` value
     * and stash it in the session for callback validation.
     */
    public static function generateState(): string
    {
        $state = Str::random(40);
        session()->put('cierra-auth.state', $state);

        return $state;
    }

    /**
     * Pull and forget the session-bound OAuth `state`. Single-use.
     */
    public static function pullState(): ?string
    {
        return session()->pull('cierra-auth.state');
    }

    // get user function
    public static function user()
    {
        return FacadesAuth::user();
    }
}
