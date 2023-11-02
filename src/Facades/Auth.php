<?php

namespace Cierra\Auth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Cierra\Auth\Skeleton
 */
class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Cierra\Auth\Auth::class;
    }
}
