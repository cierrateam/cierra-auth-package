<?php

namespace Cierra\Auth\Services;

use Cierra\Auth\Contracts\TeamResolver;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * No-op team resolver for apps that don't need post-login team handling
 * (single-tenant apps, or apps where team membership is managed elsewhere).
 */
class NullTeamResolver implements TeamResolver
{
    public function resolve(Authenticatable $user): Authenticatable
    {
        return $user;
    }
}
