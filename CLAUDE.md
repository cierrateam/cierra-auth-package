# CLAUDE.md — cierra-auth-package

Composer package that consuming Laravel apps install to authenticate against `admin.cierra.ai` (Passport 13 OAuth2 provider) and enforce **per-application licensing**.

## Stack
- PHP 8.2+, Laravel 11/12/13
- Package namespace: `Cierra\Auth\`
- Repo: `cierrateam/cierra-auth-package` (packagist private)
- Current version: 0.2.4 (on `main`)

## Package Structure
```
src/
  AuthServiceProvider.php       ← registers routes, config, commands
  Auth.php                      ← facade target (OAuth URL builder, token exchange)
  Controllers/
    AuthController.php          ← login/callback/logout
  Commands/
    GenerateOAuthClientCommand.php
    CierraAuthCommand.php
routes/
  web.php                       ← /cierra-auth/login + /cierra-auth/callback + /cierra-auth/logout
config/
  cierra-auth.php               ← host, client_id, client_secret, redirect_after_logout
database/migrations/
  2024_12_29_101105_add_cierra_auth_team_id_to_users_table.php
  2023_11_03_101105_adjust_users_table.php
```

## Current Flow
1. User hits any protected route → `redirect('/cierra-auth/login')`
2. `AuthController@login` → `redirect(Auth::getLoginUrl())` (builds `/oauth/authorize?...`)
3. admin.cierra.ai authenticates → redirects back to `/cierra-auth/callback?code=...`
4. `AuthController@callback` exchanges code for token, fetches `/api/user`, syncs User+Team locally, `Auth::login()`.

## What We're Adding (this PR)
- **License enforcement** — a middleware that blocks login if user doesn't have required license/feature/seat for this app.
- **Feature-check helper** — `Cierra\Auth\Facades\License::has('analytics')` usable in blades/controllers.
- **Webhook receiver** — `/cierra-auth/webhook` route that admin.cierra.ai hits on `license.suspended` etc., signed with HMAC; invalidates cache and optionally logs users out.
- **Context cache** — after callback, pull `/api/me/context` (one call, user + team + licenses + features) and cache per-user with short TTL.
- **New config keys** in `config/cierra-auth.php`:
  - `required_application_slug` (str, default from env `CIERRA_APP_SLUG`)
  - `required_features` (array, default `[]`)
  - `require_active_seat` (bool, default `true`)
  - `on_license_missing` (`redirect|403|abort`, default `redirect`)
  - `license_missing_redirect` (str, default `/cierra-auth/no-license`)
  - `webhook_secret` (str, from env `CIERRA_AUTH_WEBHOOK_SECRET`)
  - `context_cache_ttl` (int seconds, default `300`)

## Backward Compatibility
- Consuming apps on 0.2.x must keep working. New features OFF by default unless `required_application_slug` is set.
- Migrations are additive; no destructive changes.

## Testing
```bash
composer test                   # orchestra/testbench Pest
vendor/bin/pint
```

## Conventions
- All HTTP calls via `Illuminate\Support\Facades\Http`.
- Cache via `Cache::tags(['cierra-auth', 'user:'.$id])` where supported (redis/memcached).
- Log channel: `cierra-auth` (fallback to default stack).
- Never store the access token in a session key larger than necessary (Laravel cookie driver is 4KB).
