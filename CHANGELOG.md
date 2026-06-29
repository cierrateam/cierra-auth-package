# Changelog

All notable changes to `cierra-auth-package` will be documented in this file.

## 0.5.0 — Central app-access verdict - 2026-06-29

### Added

- `LicenseContext::canAccess(string $slug): ?bool` and `accessReason(string $slug): ?string`,
  reading the per-application access verdict now returned by admin.cierra.ai in
  the `applications` block of `/api/me/context`.
- `License::canAccess(?string $slug = null)` facade helper for blades/controllers.

### Changed

- `EnforceLicense` middleware now prefers the **central access verdict** as the
  single source of truth: free / public apps are allowed without a license,
  license-required apps still require an active license (+ seat). When the
  server returns no verdict for the slug (older admin.cierra.ai), it falls back
  to the previous license/seat checks, so behaviour is unchanged against older
  servers. `required_features` gating still applies in both paths.

No config changes and no breaking changes — existing apps keep working.

## 0.4.0 — Pluggable TeamResolver - 2026-05-29

### Changed

Post-login team-bootstrap is now resolved through a `Cierra\\Auth\\Contracts\\TeamResolver` contract.

- **Default:** `JetstreamTeamResolver` — fully backwards compatible (same calls, same Botflow AccessGroup integration). No action needed for existing apps.
  
- **For non-Jetstream apps** (e.g. workspace-scoped team schemas): bind `NullTeamResolver` or your own implementation in your service provider:
  
  ```php
  use Cierra\\Auth\\Contracts\\TeamResolver;
  use Cierra\\Auth\\Services\\NullTeamResolver;
  
  \$this->app->bind(TeamResolver::class, NullTeamResolver::class);
  
  ```
- `App\\Models\\Team` / Botflow references are now guarded by `class_exists` / `method_exists`.
  

Fixes `Call to undefined method App\\Models\\User::ownedTeams()` for host apps with a non-Jetstream User model (e.g. crm.cierra.ai).

## 0.4.0 - 2026-05-29

### Changed — Pluggable team handling (post-login)

The post-login team-bootstrap (previously hard-coded to Jetstream-style `ownedTeams()` / `currentTeam` / `switchTeam()` calls and a `personal_team` / `user_id` Team schema) is now resolved through a `Cierra\Auth\Contracts\TeamResolver` contract.

- Default binding: `Cierra\Auth\Services\JetstreamTeamResolver` — fully backwards compatible with 0.3.x behavior; same code path, same side-effects, same Botflow `AccessGroup` integration when present.
  
- Host apps with a non-Jetstream team model (e.g. workspace-scoped many-to-many teams, multi-tenant CRMs) can bind their own resolver in a service provider:
  
  ```php
  // app/Providers/AppServiceProvider.php
  use Cierra\Auth\Contracts\TeamResolver;
  use Cierra\Auth\Services\NullTeamResolver;
  
  public function register(): void
  {
      $this->app->bind(TeamResolver::class, NullTeamResolver::class);
  }
  
  ```
- Shipped resolvers: `JetstreamTeamResolver` (default), `NullTeamResolver` (no-op for apps that manage teams elsewhere).
  
- Removed: `AuthController::handleUserTeams()` and `AuthController::createTeam()` (logic moved to `JetstreamTeamResolver`). Public surface of `AuthController` unchanged.
  
- The `App\Models\Team` and `Cierra\LaravelBotflow\Models\AccessGroup` references inside `JetstreamTeamResolver` are now `class_exists` / `method_exists`-guarded so the package no longer hard-requires those classes on apps that bind a different resolver.
  

Fixes: `Call to undefined method App\Models\User::ownedTeams()` for host apps with a workspace-scoped team schema (e.g. crm.cierra.ai).

## v0.3.0 — Licensing v1 - 2026-04-24

License facade, EnforceLicense middleware, webhook receiver. Zero breaking changes from 0.2.x — all new behavior is config-gated OFF by default.

See CHANGELOG.md for full notes and upgrade path.

## 0.3.0 - 2026-04-24

### Added — Licensing v1

- **`License` facade** with feature-check helpers: `License::has('analytics')`, `License::active()`, `License::plan()`, `License::seats()`, `License::expiresAt()`
- **`EnforceLicense` middleware** (alias: `license`) — gates routes by application slug, features, and active-seat requirement
- **Webhook receiver** `POST /cierra-auth/webhook` with HMAC-SHA256 signature verification (`X-Cierra-Signature: sha256=...`). Automatically flushes context cache and dispatches `Cierra\Auth\Events\LicenseChanged` event for host apps to hook into
- **`ContextService`** — caches `/api/me/context` bundle (user + team + licenses + features) per-user; TTL configurable
- **`LicenseContext`** DTO with `hasFeature`, `hasApplicationLicense`, `hasSeat`, `plan`, `expiresAt`, `seats`
- **`/cierra-auth/no-license`** fallback route + publishable Blade view
- **Callback hook**: `AuthController@callback` now warms the context cache (fail-open on error)

### New config keys (all default OFF for BC)

- `required_application_slug` (env `CIERRA_APP_SLUG`)
- `required_features` (array)
- `require_active_seat` (bool, default true)
- `on_license_missing` (`redirect`|`abort`, default `redirect`)
- `license_missing_redirect` (default `/cierra-auth/no-license`)
- `webhook_secret` (env `CIERRA_AUTH_WEBHOOK_SECRET`)
- `context_cache_ttl` (seconds, default 300)
- `log_webhook_payloads` (bool, default false)

### Upgrading from 0.2.x

Zero breaking changes. Add the new env vars (`CIERRA_APP_SLUG`, `CIERRA_AUTH_WEBHOOK_SECRET`) and optional middleware `->middleware('license')` where you want enforcement. Without config the package behaves exactly as 0.2.x.

## 0.2.7 - 2025-11-24

**Full Changelog**: https://github.com/cierrateam/cierra-auth-package/compare/0.2.6...0.2.7

## 0.2.6 - 2025-11-24

### What's Changed

* Fix command registration by calling parent::boot() in AuthServiceProvider by @Copilot in https://github.com/cierrateam/cierra-auth-package/pull/9

### New Contributors

* @Copilot made their first contribution in https://github.com/cierrateam/cierra-auth-package/pull/9

**Full Changelog**: https://github.com/cierrateam/cierra-auth-package/compare/0.2.5...0.2.6

## 0.2.5 - 2025-11-24

### What's Changed

* Implement Temp-Client Generation in Cierra Auth Package by @codegen-sh[bot] in https://github.com/cierrateam/cierra-auth-package/pull/7

### New Contributors

* @codegen-sh[bot] made their first contribution in https://github.com/cierrateam/cierra-auth-package/pull/7

**Full Changelog**: https://github.com/cierrateam/cierra-auth-package/compare/0.2.4...0.2.5

## Feat: Register apps on login to the admin instance - 2023-12-04

Now, if the env var CIERRA_APP_ID is set, we'll register the app as active app inside the admin panel, so you have a quick link on the dashboard and it's hidden from the marketplace

## Fix: Create teams for non-ai access group projects - 2023-12-04

For projects where is no botflow is installed, we don't create acecss groups
