<?php

namespace Cierra\Auth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getLoginUrl()
 * @method static \Illuminate\Contracts\Auth\Authenticatable|null user()
 * @method static bool updateMailSignature(string $signature, ?string $position = null)
 *
 * @see \Cierra\Auth\Auth
 */
class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Cierra\Auth\Auth::class;
    }
}
