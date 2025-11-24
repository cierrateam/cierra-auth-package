# Changelog

All notable changes to `cierra-auth-package` will be documented in this file.

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
