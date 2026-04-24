# Changelog

All notable changes to `cierra-auth-package` will be documented in this file.

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
