# Changelog

All notable changes to `cierra-auth-package` will be documented in this file.

## Feat: Register apps on login to the admin instance - 2023-12-04

Now, if the env var CIERRA_APP_ID is set, we'll register the app as active app inside the admin panel, so you have a quick link on the dashboard and it's hidden from the marketplace

## Fix: Create teams for non-ai access group projects - 2023-12-04

For projects where is no botflow is installed, we don't create acecss groups
