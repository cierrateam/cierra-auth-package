<?php

namespace Cierra\Auth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Resolves / provisions the authenticated user's team after OAuth login.
 *
 * The default implementation (JetstreamTeamResolver) replicates the
 * legacy Jetstream-style behavior (ownedTeams / currentTeam / switchTeam,
 * `personal_team`, optional Botflow AccessGroup).
 *
 * Host apps that use a different team model (workspace-scoped, multi-team,
 * etc.) can bind their own implementation in the service container, or
 * use NullTeamResolver if no team handling is required.
 */
interface TeamResolver
{
    /**
     * Ensure the user has a team context after login. May return a fresh
     * model instance (e.g. after switching teams).
     */
    public function resolve(Authenticatable $user): Authenticatable;
}
