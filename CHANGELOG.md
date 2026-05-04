# Changelog

All notable changes to ci4-admin-starter will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **`AdminFilter`** no longer hardcodes the admin-permission whitelist. The list of permission codes that grant admin entry now lives in `Config\AdminAccess::$permissions` (env-overridable via `ADMIN_PERMISSIONS`), so adding a new admin module no longer requires editing the filter source.
- **`FileUploadRequest`** performs two-stage MIME validation: (1) the standard CI4 `mime_in[]` rule against the client-reported MIME, then (2) a fileinfo-based real-MIME check via `checkRealMime()` against the per-extension whitelist. A `evil.php` renamed to `evil.jpg` with a forged `Content-Type` is now rejected.
- **`Config\Security::$regenerate = false`** is preserved (multi-tab admin workflow), but the long comment on the property now documents the trade-off explicitly so a future audit doesn't re-flag it.
- **`files/partials/list_section.php`** view-mode preference moved from `localStorage` to `sessionStorage` — aligns with the architecture rule of not stashing state outside the server-side session, and avoids cross-user persistence on shared computers.
- **`composer.json`** requires CodeIgniter `^4.5` (was `^4.4`). The lock file already shipped 4.7.x; this just tightens the floor and unblocks 4.5+ features.
- **Tailwind, Alpine, and Lucide** are now built or vendored locally (`npm run build:all`). The layout falls back to the pinned jsdelivr CDN URLs only when the vendored copies are missing — keeping a fresh-clone smoke path while removing the runtime CDN dependency from production.

### Added
- **`Config\AdminAccess`** — central, env-overridable list of permission codes that grant entry to `/admin/*`. Reads `ADMIN_PERMISSIONS` (comma-separated) from `.env`.
- **`<meta name="session-expires-at">`** emitted by `BaseWebController.viewData` and consumed by `bootSessionExpiryWatcher()` in `app.js`. Logs a console warning + emits a `session:expiring-soon` window event 60s before the JWT expires; downstream UI can hook this to show a banner / save-warning. Avoids the surprise 401 mid-action.
- **`HEALTHCHECK`** in the Dockerfile — probes the PHP-FPM listener via PHP `fsockopen` (no curl/nc dependency).
- **`build:vendor` / `build:all`** npm scripts that copy `node_modules/alpinejs/dist/cdn.min.js` and `node_modules/lucide/dist/umd/lucide.min.js` into `public/assets/vendor/`.
- **5 new MIME-validation tests** in `tests/unit/Modules/Files/Requests/FileUploadRequestTest.php`: real-MIME mismatch detection for renamed `.php`, ZIP disguised as PDF, unknown extensions, and consistent PNG happy-path.
- **`AdminFilter` config-driven test** — verifies that overriding `Config\AdminAccess::$permissions` actually changes who passes the gate.

## [1.1.0] — 2026-04-30

### Added
- `bin/make-module.sh` scaffolding script for generating new admin modules with case-insensitive collision detection and FQCN-aware idempotency check
- `bin/remove-module.sh` with symmetric namespace guard to safely remove generated modules
- `PATCH` method support in `ApiClient`
- Files module: category filters, trash view, file detail panel, bulk actions, and file picker; `FileApiService` extended with trash, metadata, replace, and bulk operations
- Profile: avatar upload routed through the Files API
- Dashboard: per-component health panel reporting `up` / `degraded` / `down` per service
- Audit log: result and severity filter controls
- Translations for files, profile, dashboard, and common table column labels (EN + ES)
- UI helpers: `check_tone_badge()`, `input_class()`, and new Lucide icon aliases
- Proactive token refresh in `ApiClient` — refreshes when the access token expires within 30 s, avoiding the unnecessary 401 round-trip
- Exponential backoff retry in `ApiClient` for 5xx errors (250 ms / 500 ms)
- Pre-commit hook (PHPStan + CS-Fixer) installed automatically via `composer install`
- Environment-variable support for non-interactive installs

### Changed
- PHP requirement raised from `^8.1` to `^8.2`
- `SessionKeys` migrated from class constants to a PHP 8.1 backed `enum SessionKeys: string`; all call sites updated to `SessionKeys::CASE->value`
- `FormRequest` classes consolidated to `app/Support/Requests/` — duplicate `app/Requests/` directory removed
- Tailwind CSS upgraded from 3.4.19 to 4.2.4
- ESLint upgraded from 9.39.4 to 10.2.1
- Catalog reference module removed

### Fixed
- Dashboard: `is_image` key coerced to `false` when absent from API response
- Dashboard health endpoint now uses the correct `skip_prefix` flag

## [1.0.1] — 2026-04-16

### Security
- **(PR #7)** Updated all Composer dependencies to apply critical security patches

### Added
- **(PR #7)** Claude agent configuration files for AI-assisted development workflows

## [1.0.0] — 2026-04-16

### Added
- **(PR #1)** Initial server-rendered admin frontend consuming the `ci4-api-starter` REST API:
  - `ApiClient` library with session-based JWT storage and automatic token refresh on 401
  - Auth module: login, register, password reset, email verification, Google OAuth, locale switching
  - Dashboard: API health indicator, recent file activity, metrics overview
  - Profile: edit profile, change password, avatar display, resend verification email
  - Files module: drag-and-drop upload with progress bar, server-driven table, download and delete
  - Users admin: full CRUD, invitation-based creation, approval workflow, superadmin role management
  - Audit logs admin: searchable and filterable log table with detail view and entity drill-down
  - API keys admin: full CRUD with one-time secure key display
  - Metrics admin: summary cards and time-series charts with period and date range filters
  - Internationalization: English and Spanish; locale switching via session and `LocaleFilter`
  - Tailwind CSS local build pipeline (`npm run dev:css` / `build:css`) with Alpine.js interactivity
  - `FormRequest` validation layer for all admin forms; `BaseApiService` pattern for API communication
  - PHPStan static analysis, PHP-CS-Fixer PSR-12, and GitHub Actions CI/CD pipeline
  - Unit and feature test suite: auth flows, file upload/download/delete, API key CRUD, filter and sort forwarding
  - Docker environment and Makefile for development

[unreleased]: https://github.com/dcardenasl/ci4-admin-starter/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/dcardenasl/ci4-admin-starter/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/dcardenasl/ci4-admin-starter/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/dcardenasl/ci4-admin-starter/releases/tag/v1.0.0
