<?php

namespace Cierra\Auth;

class Auth
{
    public static function getLoginUrl()
    {
        $queryParams = [
            'client_id' => config('cierra-auth-package.client_id'),
            'redirect_uri' => route('cierra-auth.callback'),
            'response_type' => 'code',
            'scope' => '',
        ];

        return config('cierra-auth-package.host').'/oauth/authorize?'.http_build_query($queryParams);
    }
}
