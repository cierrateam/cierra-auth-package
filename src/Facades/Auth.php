<?php

namespace Cierra\Auth\Facades;

use Cierra\Auth\Skeleton;
use Illuminate\Support\Facades\Facade;

/**
 * @see Skeleton
 */
class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Cierra\Auth\Auth::class;
    }
}
