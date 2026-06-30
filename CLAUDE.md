# CLAUDE.md — cierra-auth-package

Composer package that consuming Laravel apps install to authenticate against `admin.cierra.ai` (Passport 13 OAuth2 provider) and enforce **per-application licensing**.

## Stack
- PHP 8.2+, Laravel 11/12/13
- Package namespace: `Cierra\Auth\`
- Repo: `cierrateam/cierra-auth-package` (packagist private)
- Current version: 1.2.0 (on `main`); 1.1.0 published (central app-access verdict)

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

## Already Shipped (v0.3.0–v1.2.0) — the section below was the original plan; it's now DONE
- ✅ **License enforcement** — `Cierra\Auth\Middleware\EnforceLicense` (alias `license`) blocks access without the required access/license/feature/seat.
- ✅ **Central app-access verdict (v1.1.0)** — the middleware now prefers the per-app verdict from `/api/me/context` (`applications[]`, `can_access` + `reason`): **free/public apps pass without a license**, licensed apps still need an active license (+ seat). Falls back to the license check against older servers. Helpers: `LicenseContext::canAccess()/accessReason()`, `License::canAccess()`.
- ✅ **Feature-check helper** — `Cierra\Auth\Facades\License::has('analytics')` usable in blades/controllers.
- ✅ **Webhook receiver** — `/cierra-auth/webhook`, HMAC-verified (`X-Cierra-Signature`); flushes context cache + dispatches `LicenseChanged`.
- ✅ **Context cache** — `ContextService` pulls `/api/me/context` and caches per-user (TTL `context_cache_ttl`, default 300s), fail-open.
- ✅ **Mail signature sync (v1.2.0)** — migration adds nullable `position` + `mail_signature` to `users`; the callback syncs both from `/api/user`. `Auth::updateMailSignature($html, $position = null)` writes the default signature back to the central profile (`PUT /api/me/mail-signature`) and mirrors it locally. This is the mechanism that flows a signature created in the signature generator out to the CRM (and any other consuming app) on next login.
- Config keys live in `config/cierra-auth-package.php`:
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
