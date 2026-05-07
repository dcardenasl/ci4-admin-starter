# Changelog

All notable changes to ci4-admin-starter will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Operacional / Deploy — Bloque B10** (2026-05-07):
  - **`RequestIdHolder` + `X-Request-ID` propagation** (audit B10.1) — `app/Libraries/RequestIdHolder.php` is a static registry mirroring api-starter's pattern. `ApiClient::resolveRequestId()` reads the incoming request's `X-Request-ID` header (or generates a UUID v4 fallback) and stamps it on the holder + outgoing API request — so admin and api logs join cleanly in any aggregator.
  - **`JsonFileHandler`** (audit B10.2) — `app/Libraries/Logging/JsonFileHandler.php`: drop-in CI4 log handler that emits one JSON line per record at `writable/logs/log-json-YYYY-MM-DD.log`. Aggregator-friendly (ELK / Splunk / Loki / Datadog), no Monolog dependency. Self-disables (`handles = []`) when `LOG_FORMAT != json` so the default-text deployment pays nothing. Tags every line with `request_id` from `RequestIdHolder` and a constant `service: "ci4-admin-starter"`. 4 unit tests in `tests/unit/Libraries/Logging/JsonFileHandlerTest.php`.
  - **`Config\Session` env-driven driver** (audit B10.3) — the `$driver` property now resolves from `SESSION_DRIVER` (`file` / `redis` / `database` / `memcached`) at construction time, with `SESSION_SAVE_PATH` for the connection string. Unknown values fall back to `FileHandler` with a warning. New "Session storage for multi-server" section in `docs/DEPLOYMENT.md` with the Redis recipe and a note on the token-refresh race trade-off.
  - **`MaintenanceFilter`** (audit B10.4) — alias `maintenance`, wired in `globals.before`. `MAINTENANCE_MODE=true` returns `503 Service Unavailable` with a `Retry-After` header. Bypasses `/health`, `/ping`, `/ready`, `/live` so orchestrators keep probing. Renders JSON for `Accept: application/json` requests and a minimal styled HTML page otherwise. Custom message via `MAINTENANCE_MESSAGE`, retry seconds via `MAINTENANCE_RETRY_AFTER`. 7 unit tests in `tests/unit/Filters/MaintenanceFilterTest.php`.
  - **`.github/workflows/release.yml`** (audit B10.5) — on `v*.*.*` tag push, extracts the matching `## [VERSION]` section from `CHANGELOG.md` via inline awk and creates a GitHub Release with those notes. Soft-fails when the release already exists (re-tag scenario) by editing instead of failing.

### Added
- **Test maturity — Bloque B9** (2026-05-07):
  - **`PermissionFilterTest`** (audit B9.3) — 5 unit tests in `tests/unit/Filters/` pinning the gating contract: allows when permission held, redirects browser requests to `/dashboard`, returns JSON 403 for AJAX, denies fail-closed on empty arguments. Audit was partly outdated — `BadgeHelperTest` and `ApiClientTest` already existed.
  - **`scripts/check-coverage.php`** (audit B9.4) — clover-XML parser that exits non-zero when line coverage is below the supplied threshold (default 70%). New composer alias `coverage:check`. Wired into `.github/workflows/ci.yml` as a soft-fail step (PHP 8.2 lane only; `continue-on-error: true`) until a confirmed baseline lets us flip it to a hard gate.
  - **B9.1 closed as audit false-positive:** admin feature tests already mock the API surface via `Services::injectMock` (verified across `ApiKeyFlowTest`, `AuthLogoutFlowTest`, etc.). The audit's "tests llaman al API real" finding was outdated.

### Added
- **Frontend hardening — Bloque B8** (2026-05-07):
  - **`asset_url()` / `asset_version()` helper** (audit B8.1) — `app/Helpers/asset_helper.php`, autoloaded via `Config\Autoload::$helpers`. Reads `ASSET_VERSION` env (production-correct, set per-deploy) or falls back to file mtime (dev convenience). Wired into `app.php` / `auth.php` layouts and the vendored Alpine/Lucide tags in `head.php`. 6 unit tests in `tests/unit/Helpers/AssetHelperTest.php`. Documented in `docs/DEPLOYMENT.md` with the recommended `git rev-parse --short HEAD` pattern. PHPStan got a `base_url()` shim in `phpstan-bootstrap.php`.
  - **i18n parity check** (audit B8.3) — `scripts/i18n-check.php` validates EN/ES file and key parity for both global `app/Language/` and per-module `app/Modules/{Module}/Language/` trees. New `composer i18n-check` script and matching step in `.github/workflows/ci.yml`. Adapted from `ci4-api-starter`'s heavier checker, trimmed to just parity (admin doesn't have the hardcoded-exception scan surface).
  - **`field_aria_attrs()`, `field_error_id()`, ARIA-aware `render_field_error()`** (audit B8.4) — `app/Helpers/form_helper.php` gains `aria-invalid="true"` + `aria-describedby="field-error-<safe>"` emission when a field has a stored error, and `aria-required="true"` when the caller asserts. The error `<p>` now carries a stable `id` + `role="alert"` so screen readers announce dynamically. Existing rendering output preserved otherwise (back-compat). 7 new unit tests in `FormHelperTest`. Confirm modal already had Escape + tab focus trap (verified during the audit; no change needed).
  - **`revokeTokenWithRetry()`** (audit B8.5) — `AuthController::logout()` previously called the API logout endpoint exactly once and silently dropped failures, leaving the JWT live on the API. Now retries once with a 250ms backoff (covers transient network blips) and logs a warning to the security audit log when both attempts fail. Local session destruction is unconditional to keep logout snappy. Covered by `AuthLogoutFlowTest` (2 existing tests updated for the retry semantics + 1 new test asserting transient-blip success on second attempt).

### Changed
- **CI `npm run lint:all`** (audit B8.2) — `.github/workflows/ci.yml` now runs `lint:all` (eslint over `public/assets/js/**/*.js`) instead of `lint:js` (only `app.js`). `lint-staged` widened to the same `public/assets/js/**/*.js` pattern so Husky pre-commit catches issues in any future JS file, not just `app.js`. Audit had a partial false positive (CI did already run `npm run lint:js`); the actual gap was scope.

### Added
- **Hardened multi-stage Dockerfile + `.dockerignore`** (audit B5.3, 2026-05-06) — three-stage build (`composer-build` → `asset-build` → runtime PHP-FPM Alpine). Frontend assets (Tailwind + vendored Alpine/Lucide) are now baked into the image so production no longer needs `npm install`/`build`. Drops to `USER www-data` before `CMD` (was running as root). `--no-dev` for both composer and the dropped phpunit/phpstan/cs-fixer artifacts. Resulting image: **123 MB** (was unmeasured but ballooned by `node_modules` + `.git` + tests + dev-deps). HEALTHCHECK probe (PHP `fsockopen` on :9000) preserved. Removed the broken `docker-php-ext-enable fileinfo` line — fileinfo is statically compiled into PHP 8.2.
- **`GET /health` endpoint** (audit B5.2, 2026-05-06) — public, lightweight liveness probe in new `App\Modules\System` module. Returns JSON `{ok, status, service, version, timestamp, checks}` with HTTP 200 when healthy or 503 if `WRITEPATH` is not writable. Bypasses auth/admin filters by living outside the `auth`-grouped routes; CSRF doesn't apply to GET. 2 feature tests in `tests/feature/HealthEndpointTest.php`. Suitable for k8s liveness/readiness probes and load-balancer health checks.
- **`SecurityHeadersFilter`** (audit B5.1, 2026-05-06) — emits `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (camera/microphone/geolocation/payment/usb/magnetometer/gyroscope all empty), and `Strict-Transport-Security` in production. Registered as alias `securityheaders` in `Config/Filters.php`, wired into `globals.after` before CI4's native `secureheaders`. Closes parity gap with `ci4-api-starter`. 6 unit tests in `tests/unit/Filters/SecurityHeadersFilterTest.php`.

### Changed
- **`phpstan-bootstrap.php`** no longer pre-defines `ENVIRONMENT='testing'` — leaving it runtime-unknown so legitimate `ENVIRONMENT === 'production'` branches are not flagged as `identical.alwaysFalse`. The matching `Constant ENVIRONMENT not found` warning is suppressed in `phpstan.neon` (mirrors api-starter convention). Removed the now-stale `@phpstan-ignore identical.alwaysFalse` annotation in `app/Helpers/ui_helper.php`.
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
