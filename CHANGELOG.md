# Changelog

All notable changes to ci4-admin-starter will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.5.0] — 2026-05-29

### Changed

- Updated `package-lock.json` with current `@tailwindcss/oxide-wasm32-wasi` nested dependency tree.

## [2.4.0] — 2026-05-29

### Added

- **Vendor Management:** Introduced `scripts/build-vendor.js` for improved cross-platform vendor asset management.

### Changed

- **Dependencies:** Updated `package.json` and `package-lock.json` for frontend stability.
- **Assets:** Rebuilt `public/assets/css/app.css` to align with latest styling requirements.

## [2.3.0] — 2026-05-28

### Added

- **`install.sh` non-interactive mode** — added `--yes` / `-y` flag (and `CI4_CONFIRM=y`) that runs the template configuration with built-in defaults and auto-confirms, so the installer can be driven unattended by orchestrators and CI.

### Changed

- **`bin/register-service.php` rewritten on top of `nikic/php-parser`** — Services.php factory injection now mutates the AST instead of splicing strings/regex, making `use` insertion and factory wiring more robust. Idempotency checks require the `public static function` shape, and all error output goes to STDERR.

## [2.2.0] — 2026-05-27

### Added

- **Expanded icon map** — 15 new semantic aliases in `ui_helper::ui_icon()`: `cart`, `warehouse`, `box`, `truck`, `wallet`, `credit-card`, `bank`, `settings`, `mail`, `bell`, `calendar`, `map-pin`, `tag`, `ticket`, `store`. Useful for e-commerce and logistics domain modules.

### Changed

- **PHPStan configuration modernised** — migrated to CI4-aware extensions (`phpstan-codeigniter`, `phpstan-strict-rules`, `phpstan-deprecation-rules`). PSR-4 class-map now includes `App\` and `Config\` namespaces so PHPStan resolves CI4 config classes without `@extends` casts. PHPStan dependencies updated in `composer.lock`.
- **`bin/make-module.sh` logging improved** — scaffolding steps print structured progress lines that make the output easier to trace in CI and automated installs.
- **CodeIgniter 4 updated to v4.7.3** (`composer.lock`).

## [2.1.1] — 2026-05-24

### Fixed

- `bin/make-module.sh` now serializes scaffolding steps and aligns generated test stubs to the module's actual service interface, preventing phantom test failures on fresh module generation.

## [2.1.0] — 2026-05-23

### Added

- **Async dashboard widget endpoints** — dashboard page now renders instantly with skeleton placeholders and fetches each widget independently (`GET /dashboard/widgets/stats`, `/widgets/health`, `/widgets/recent-files`, `/widgets/activity`). Eliminates blocking 2-3 API calls on cold render.
- **Multi-backend health widget** — dashboard health panel now shows hub, domain app, and BFF gateway as separate cards. Cards auto-omit when the respective `baseUrl` is unconfigured (empty string).
- **BFF gateway health integration** (`BffApiClient`, `Config\BffApiClient`, `Services::bffApiClient()`) — thin HTTP client subclass targeting `ci4-bff-starter`. Optional: set `bffApiClient.baseUrl = http://localhost:8088` to enable BFF health monitoring.
- **CI security workflow** (`.github/workflows/security.yml`) — automated vulnerability scanning via `composer security` and `npm audit`. Runs on `push` to main/dev branches and on pull requests.

### Changed

- **Dependency stability** — `lint-staged` bumped from v14 to v17; `@tailwindcss/cli` constrained to `^4`. Node.js CI floor raised to `^24.0.0` to match `engines.node` declaration.

### Fixed

- **CI shell injection in release workflow** — fixed variable interpolation in release notes extraction (previously vulnerable to backticks in CHANGELOG content; now properly escaped via env-pass).

## [2.0.0] — 2026-05-19

This release realigns the admin to the v2.0 contract of `ci4-api-starter` (permission-based authorization, no `users.role`), drives admin access from config instead of a hardcoded filter list, and hardens the deployment surface: Dockerfile multi-stage build, security headers, public `/health` endpoint, JSON logging with `X-Request-ID` propagation, maintenance-mode short-circuit, asset cache-busting, two-stage MIME validation, and a tag-driven GitHub Release workflow. Also migrates the CSS build to Tailwind v4 (CSS-first config), tightens the Node engine floor, ships the workaround that unblocks the trash UI against `ci4-api-starter@v2.1.0`, and bumps the `codeigniter4/framework` floor to `^4.7`.

### Changed

- **`codeigniter4/framework` constraint bumped from `^4.5` to `^4.7`** — locks to the current stable CI4 (v4.7.2). README requirements section corrected from PHP 8.1 → PHP 8.2 (matches `composer.json`) and Node 16+ → Node 20.19+ (matches `package.json` engines).

### ⚠️ Breaking Changes

- **IAM contract realigned with API v2.0.** The session `user` object now exposes `permissions: string[]` (was `user.role`). The legacy `has_admin_access()` helper has been removed; all UI gating and route filtering routes through `has_permission(string $code)` (from `app/Helpers/auth_helper.php`). Permission codes use the **dot separator** (`iam.admin-access`, `users.write`, `metrics.read`).
- **`AdminFilter` is driven by `Config\AdminAccess`** (env-overridable via `ADMIN_PERMISSIONS`). The hardcoded admin-permission list has been removed from the filter source. Downstream consumers that customised the filter must port their list to the config class.
- **Email is immutable for non-superadmin actors.** `UserUpdateRequest::payload()` strips `email` from outbound payloads unless the actor is `is_superadmin()`; the API enforces `403 Iam.cannotModifyEmail` as defence in depth. Self-edit of `email` is never accepted — the profile view renders the field read-only.
- **Profile self-edit flows through `PATCH /auth/me`.** `ProfileController` no longer calls `PUT /users/{id}` for self-updates. Allowlist: `first_name`, `last_name`, `avatar_url`. Any code that overrode `ProfileController::update()` to send arbitrary fields must rework against the dedicated endpoint.
- **`AppUserMembershipController` removed.** Role assignment to users now happens through `UserController` (the user create/edit forms accept `role_ids[]`). The standalone "membership" admin UI has been deleted.
- **`SecurityHeadersFilter` enabled by default in `globals.after`.** Emits `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy` (empty for camera/microphone/geolocation/payment/usb/magnetometer/gyroscope), and `Strict-Transport-Security` in production. Embedded third-party iframes, external script tags, and inline scripts may now be blocked — review custom views for CSP compatibility.
- **`Config\Session` resolves the driver from `SESSION_DRIVER`** (`file` / `redis` / `database` / `memcached`). The default still falls back to `FileHandler` when unset, but multi-server production deployments must set this explicitly.
- **`Config\Security::$regenerate` is documented as intentional `false`.** The behaviour did not change, but consumers that audited for "always-on session-id regeneration" should read the explanatory comment on the property before flagging it.
- **Dockerfile rebuilt as a three-stage build** (`composer-build` → `asset-build` → runtime PHP-FPM Alpine). Production no longer needs `npm install`/`build` at runtime (assets baked in). Runs as `USER www-data` instead of root. Existing `Dockerfile` customisations may need to re-base.
- **`composer.json` requires CodeIgniter `^4.5`** (was `^4.4`).
- **Frontend dependencies vendored locally.** Tailwind, Alpine, and Lucide are now built or copied via `npm run build:all` into `public/assets/css/` and `public/assets/vendor/`. The layout falls back to the pinned jsdelivr CDN URLs only when the vendored copies are missing — keeping a fresh-clone smoke path while removing the runtime CDN dependency from production. Deployment pipelines must run `npm ci && npm run build:all` before publishing.
- **PHP `^8.2`** (locked in at v1.1.0; restated here for downstream that may have skipped 1.1).
- **`engines.node` floor raised to `^22.22.1 || >=24`** (was `>=20.0.0`). Required by `lint-staged@17`. **Node 20 is no longer supported for the build toolchain.** Bump your CI / dev runtime to Node 22.22.x or later before installing.
- **`tailwind.config.js` removed.** Tailwind v4 is CSS-first: the equivalent config now lives in `src/css/app.css` via `@import "tailwindcss"`, an `@theme {}` block (brand color palette + fonts), `@source` directives (`app/Views`, `app/Helpers`, `public/assets/js`), and `@source inline()` declarations replacing the old JS `safelist`. Any downstream that customised `tailwind.config.js` must port their config across — `@config "../tailwind.config.js"` is technically supported by v4 but `safelist` is **not** honored when loaded that way; use `@source inline()` instead. See the Tailwind v4 upgrade guide.
- **`@tailwindcss/cli` is now a required dev dependency.** In v4 the CLI ships in a separate package; the existing `npm run dev:css` and `build:css` scripts keep working because both packages expose the same `tailwindcss` binary, but `npm ci` needs the new package to be installed.

### Added

#### Operational / deploy
- **`RequestIdHolder` + `X-Request-ID` propagation** (`app/Libraries/RequestIdHolder.php`) — mirrors api-starter's pattern. `ApiClient::resolveRequestId()` reads the incoming `X-Request-ID` header (or generates a UUID v4 fallback) and stamps it on the holder plus the outgoing API request, so admin and API logs join cleanly in any aggregator.
- **`JsonFileHandler`** (`app/Libraries/Logging/JsonFileHandler.php`) — drop-in CI4 log handler emitting one JSON line per record at `writable/logs/log-json-YYYY-MM-DD.log`. Aggregator-friendly (ELK / Splunk / Loki / Datadog), no Monolog dependency. Self-disables (`handles = []`) when `LOG_FORMAT != json` so the default-text deployment pays nothing. Tags every line with `request_id` from `RequestIdHolder` and a constant `service: "ci4-admin-starter"`.
- **`MaintenanceFilter`** (alias `maintenance`, `globals.before`). `MAINTENANCE_MODE=true` returns `503 Service Unavailable` with `Retry-After`. Bypasses `/health`, `/ping`, `/ready`, `/live`. Renders JSON for `Accept: application/json` requests and a minimal styled HTML page otherwise. Custom message via `MAINTENANCE_MESSAGE`, retry seconds via `MAINTENANCE_RETRY_AFTER`.
- **`.github/workflows/release.yml`** — on `v*.*.*` tag push, extracts the matching `## [VERSION]` section from `CHANGELOG.md` via inline awk and creates a GitHub Release with those notes. Soft-fails on re-tag.
- **Public `GET /health` endpoint** (`App\Modules\System`) — lightweight liveness probe returning JSON `{ok, status, service, version, timestamp, checks}` with HTTP 200 healthy / 503 if `WRITEPATH` is not writable. Bypasses auth/admin filters; suitable for k8s probes and load-balancer health checks.
- **`HEALTHCHECK` in the Dockerfile** — probes the PHP-FPM listener via PHP `fsockopen` (no curl/nc dependency).
- **Shared `ci4-platform` external network** in `docker-compose.yml`. The admin, hub (`ci4-api-starter`), and optionally a domain app (`ci4-domain-starter`) attach to the same bridge network so containers resolve each other by service name (`ci4-api-app`, `ci4-domain-app`) while each stack publishes its own host port. Container names switched to kebab-case (`ci4-admin-app`, `ci4-admin-web`, `ci4-admin-redis`). Setup is a one-time `docker network create ci4-platform` on the host — `docker/README.md` walks through the end-to-end flow plus the optional domain-starter extension.

#### Multi-backend support
- **`DomainApiClient` — secondary HTTP client targeting a `ci4-domain-starter`** app in parallel to the hub. Lets the admin surface entities owned by a domain backend (subscriptions, projects, campaigns…) alongside entities owned by the hub (users, IAM, files, audit) from the same panel.
  - **Config (`app/Config/DomainApiClient.php`)** mirrors `Config\ApiClient`, reads `domainApiClient.*` / `DOMAIN_API_*` env vars (default base URL `http://localhost:8090`), and extends `Config\ApiClient` so PHPStan keeps the contract aligned.
  - **Library (`app/Libraries/DomainApiClient`)** extends `ApiClient` and implements `DomainApiClientInterface` — inherits all refresh/header/upload logic, just bound to the domain config.
  - **Service factory `Services::domainApiClient()`** registered in `Config\Services` as the DI peer of `Services::apiClient()`.
  - **Scaffolding switch** — `bin/make-module.sh ... --service=hub|domain` (default `hub`) and the underlying `bin/register-service.php --client=hub|domain` flag propagate the choice into the emitted service factory call (`static::apiClient()` vs `static::domainApiClient()`).
  - **When to use which:** entities owned by the hub → `apiClient`; entities owned by a domain app → `domainApiClient`. Never mix in the same module. Documented in `CLAUDE.md`.

#### Security
- **`SecurityHeadersFilter`** — emits `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, and `Strict-Transport-Security` in production. Registered as alias `securityheaders` in `Config/Filters.php`, wired into `globals.after` before CI4's native `secureheaders`. Closes parity gap with `ci4-api-starter`.
- **Two-stage MIME validation in `FileUploadRequest`** — the standard CI4 `mime_in[]` rule against the client-reported MIME, then a fileinfo-based real-MIME check via `checkRealMime()` against the per-extension whitelist (`ALLOWED_EXTENSION_MIMES`). An `evil.php` renamed to `evil.jpg` with a forged `Content-Type` is now rejected.
- **`revokeTokenWithRetry()` in `AuthController::logout()`** — retries the API logout call once with a 250ms backoff (covers transient network blips) and logs a warning to the security audit log when both attempts fail. Local session destruction stays unconditional to keep logout snappy.

#### IAM admin UI
- **`Config\AdminAccess`** — central, env-overridable list of permission codes that grant entry to `/admin/*`. Reads `ADMIN_PERMISSIONS` (comma-separated) from `.env`.
- **`App\Modules\Iam`** — Roles and Permissions admin modules under `/admin/iam/`. Full CRUD plus inline permission-editor on role create/edit. M2M attach/detach for roles↔permissions.
- **Role assignment surfaced on the Users edit page** via `assignableRoles()` on `UserApiService` — the form submits `role_ids[]` directly, replacing the deleted standalone membership controller.
- **Inline permission editor** on role create / edit views.
- **Applications read-only browser** under `/admin/iam/applications` (gated by `superadmin`). Surfaces every registered application from the hub's `IAM Applications` resource with index (server-driven table) and detail views, reusing the existing `ApplicationApiService`. Read-only by design — applications are registered server-side via `php spark apps:bootstrap` on the hub. Sidebar entry "Applications" sits under the Identity & Access section with the `layers` icon. EN + ES strings complete.

#### Frontend hardening
- **`asset_url()` / `asset_version()` helper** (`app/Helpers/asset_helper.php`, autoloaded). Reads `ASSET_VERSION` env (production-correct, set per-deploy) or falls back to file mtime (dev convenience). Wired into `app.php` / `auth.php` layouts and the vendored Alpine/Lucide tags in `head.php`.
- **ARIA-aware form helpers** — `field_aria_attrs()`, `field_error_id()`, and an updated `render_field_error()` emit `aria-invalid="true"` + `aria-describedby="field-error-<safe>"` when a field has a stored error, and `aria-required="true"` when the caller asserts. The error `<p>` carries a stable `id` + `role="alert"` so screen readers announce dynamically.
- **`<meta name="session-expires-at">`** emitted by `BaseWebController.viewData` and consumed by `bootSessionExpiryWatcher()` in `app.js`. Logs a console warning and emits a `session:expiring-soon` window event 60 s before the JWT expires — downstream UI can hook this to show a banner / save-warning.
- **`build:vendor` / `build:all` npm scripts** that copy `node_modules/alpinejs/dist/cdn.min.js` and `node_modules/lucide/dist/umd/lucide.min.js` into `public/assets/vendor/`.

#### Dashboard
- **Async widget endpoints** — the dashboard page renders instantly with skeleton placeholders and fetches each panel independently: `GET /dashboard/widgets/stats`, `/dashboard/widgets/health`, `/dashboard/widgets/recent-files`, `/dashboard/widgets/activity`. Eliminates the 2-3 API-call blocking render that caused slow first-paint on cold caches.
- **Multi-service health panel** — the health widget shows hub, domain app, and BFF gateway as separate cards. Cards are omitted automatically when the respective `baseUrl` is unconfigured (empty string in the config), so a plain hub-only deployment sees only the hub card.

#### BFF integration (optional)
- **`BffApiClient` / `BffApiClientInterface`** (`app/Libraries/BffApiClient.php`) — thin subclass of `ApiClient` bound to `Config\BffApiClient`. Provides the same token-refresh and header-injection behaviour as the hub client but targeting the BFF gateway.
- **`Config\BffApiClient`** (`app/Config/BffApiClient.php`) — reads `bffApiClient.*` env vars (default `baseUrl = ''` — disabled). Set `bffApiClient.baseUrl = http://localhost:8088` to enable BFF health monitoring on the dashboard.
- **`Services::bffApiClient()`** registered in `Config\Services` alongside the existing `apiClient()` and `domainApiClient()` factories.
- **`Services::domainHealthApiService()`** registered — a second `HealthApiService` instance bound to `domainApiClient()`, consumed by the new health widget to probe the domain app independently of the hub.

#### Quality / CI
- **`security.yml` GitHub Actions workflow** — weekly security scan (plus push/PR to `main`): `composer audit`, `npm audit --audit-level=high`, hardcoded-secret grep, `.env` presence check, `.gitignore` validation. Matches the workflow present in `ci4-api-starter`, `ci4-domain-starter`, and `ci4-bff-starter`.
- **CI push trigger scoped to `main` and `dev`** — prevents CI from running on every feature branch push; aligns with the convention in the other platform repos.
- **`*.key` added to `.gitignore`** — required by the `security.yml` gitignore-validation step.
- **lint-staged bumped to `^17.0.5`; CI upgraded to Node 22** — lint-staged v17 requires `>=22.22.1`. `engines.node` updated to `^22.22.1 || >=24`; both `ci.yml` and `security.yml` set `node-version: '22'`. Node 20 is no longer supported for the build toolchain.
- **`scripts/i18n-check.php`** — validates EN/ES file and key parity for both global `app/Language/` and per-module `app/Modules/{Module}/Language/` trees. Wired in as `composer i18n-check` and a matching CI step.
- **`scripts/check-coverage.php`** — clover-XML parser that exits non-zero below the supplied threshold (default 70%). Composer alias `coverage:check`. Wired into `ci.yml` as a soft-fail step on the PHP 8.2 lane until a confirmed baseline lets us flip it to a hard gate.
- **`PermissionFilterTest`** — 5 unit tests pinning the gating contract: allows when permission held, redirects browser requests to `/dashboard`, returns JSON 403 for AJAX, denies fail-closed on empty arguments.
- **`AdminFilter` config-driven test** — verifies that overriding `Config\AdminAccess::$permissions` actually changes who passes the gate.
- **MIME-validation tests** — 5 new tests in `tests/unit/Modules/Files/Requests/FileUploadRequestTest.php` covering renamed `.php`, ZIP disguised as PDF, unknown extensions, and the consistent PNG happy-path.
- **`MaintenanceFilterTest`**, **`SecurityHeadersFilterTest`**, **`JsonFileHandlerTest`** — unit coverage for the new filters and the JSON log handler.
- **`AuthLogoutFlowTest`** updated for the retry semantics, plus a new test asserting transient-blip success on the second attempt.
- **CI `npm run lint:all`** — `.github/workflows/ci.yml` now runs `lint:all` (eslint over `public/assets/js/**/*.js`) instead of `lint:js` (only `app.js`). `lint-staged` widened to the same pattern.
- **Cross-module route-name collision detection in `bin/make-module.sh`** (exit 6). The scaffolder scans `app/Modules/*/Config/Routes.php` plus `app/Config/Routes.php` before generating and refuses to write when the planned `ROUTE_NAME` is already registered in another module — catching the realistic "two modules emit the same named route" mistake that would otherwise shadow URLs silently. Covered by `testMakeModuleRejectsCrossModuleRouteNameCollision` in `ScaffoldingScriptsTest`.
- **`DomainApiClientTest`** (10 unit tests) — covers config defaults, env override resolution, header injection, refresh delegation, and the `--client=domain` path through the service factory. Plus 3 new cases in `ScaffoldingScriptsTest` exercising `--service=domain` and `register-service.php --client=domain`.

### Changed

- **`AdminFilter`** no longer hardcodes the admin-permission whitelist (see Breaking Changes above for the migration path).
- **`Config\Security::$regenerate = false`** retained intentionally (multi-tab admin workflow); the long comment on the property now documents the trade-off explicitly so a future audit doesn't re-flag it.
- **`files/partials/list_section.php`** view-mode preference moved from `localStorage` to `sessionStorage` — aligns with the architecture rule of not stashing state outside the server-side session, and avoids cross-user persistence on shared computers.
- **`composer.json`** requires CodeIgniter `^4.5` (was `^4.4`). The lock file already shipped 4.7.x; this tightens the floor and unblocks 4.5+ features.
- **`phpstan-bootstrap.php`** no longer pre-defines `ENVIRONMENT='testing'` — leaving it runtime-unknown so legitimate `ENVIRONMENT === 'production'` branches are not flagged as `identical.alwaysFalse`. The matching `Constant ENVIRONMENT not found` warning is suppressed in `phpstan.neon` (mirrors api-starter convention).
- **Tailwind, Alpine, and Lucide** are now built or vendored locally (`npm run build:all`); the runtime CDN dependency is gone in production.
- **CSS build migrated to Tailwind v4** (`tailwindcss@^4.3.0` + `@tailwindcss/cli@^4.3.0`). `src/css/app.css` switches `@tailwind base/components/utilities` → `@import "tailwindcss"`. The `brand-*` palette lives in an `@theme {}` block; the old JS `safelist` (gradient classes, `odd:`/`even:`/`hover:` table variants, `py-3.5`, `text-[11px]`) is now `@source inline(...)` directives. `app/Views/layouts/partials/head.php` switches the runtime `--color-brand-*` CSS vars from RGB triplets (`239 246 255`) to full `rgb()` values so the cascade override works under v4's `color-mix()` opacity model (v3's `<alpha-value>` indirection is gone). Net effect: minified output grows from ~30 KB to ~42 KB (more defaults shipped + `@source inline` additions), build wall-time is sub-100 ms.
- **`eslint` bumped to `^10.4.0`** (was `^10.2.1`). `@eslint/js` stays on `^10.0.1` (latest of the v10 series). `lint-staged` deferred to `^16.4.0` (the v17 line requires Node `>=22.22.1`; tracked in admin `TASKS.md` as `ADM-DEP-002`).
- **`lucide` bumped to `1.16.0`** (was `0.539.0`). Icon-set compatible update; `npm run build:vendor` regenerates the vendored copy in `public/assets/vendor/`.
- **`apiClient.appKey` documented in the example env template** — the `env` file now includes the commented-out `apiClient.appKey` key with the same descriptive comment as `CLAUDE.md` and `docs/ARCHITECTURE.md`, so the option is visible on first-clone setup.
- **Deprecated `POST /files/{id}/replace` endpoint removed from the admin.** The route, controller action, service method, and interface entry have been deleted. The file detail view (`files/show.php`) now shows an inline warning when the file has active usages (resources referencing it), giving context before deletion. Tracked as pending on the API side in the workspace `TASKS.md`.
- **Admin docs governance** — `docs/INDEX.md` and `docs/es/INDEX.md` now carry an explicit "English is the source of truth, the Spanish translation may lag" banner so contributors don't expect line-for-line parity. The `CLAUDE.md` Files routes table now enumerates the full surface (trash, restore, force, bulk, replace, regenerate, metadata, usages, picker) and flags which routes still wait on API endpoints.

### Fixed

- **`FileApiService::bulk{Delete,Restore,ForceDelete}` stringify the `ids` array** before posting to the API. CI4's global `InvalidChars` filter calls `mb_check_encoding()` recursively over the JSON body and throws `TypeError` on raw integers; serialising as strings dodges the framework bug, and the API DTO casts back to `int` internally. Unblocks the trash UI end-to-end against `ci4-api-starter@v2.1.0`.
- **`DashboardController`** caches health-check, metrics-summary, and recent-files API calls in the session (60-second TTL). Eliminates redundant round-trips on repeated dashboard loads and reduces perceived latency.

### Removed

- **`has_admin_access()` helper** — replaced by `has_permission('iam.admin-access')`.
- **`AppUserMembershipController`** and its routes — role assignment lives in the Users module.
- **Hardcoded admin-permission list** in `AdminFilter` — moved to `Config\AdminAccess`.
- **Broken `docker-php-ext-enable fileinfo`** line in the Dockerfile — fileinfo is statically compiled into PHP 8.2.
- **Catalog reference module** (already gone in 1.1.0; restated here for completeness).
- **`tailwind.config.js`** — replaced by CSS-first config in `src/css/app.css` (Tailwind v4).

### Migration Guide

Upgrading from `1.1.x` directly to `2.0.0`:

1. **Reinstall dependencies and rebuild assets**: `composer install && npm ci && npm run build:all`. Production deployments must run `npm run build:all` (or `build:css` + `build:vendor`) before publishing — the runtime no longer falls back to CDN in production.
2. **Re-issue any cached JWT.** The login response shape changed on the API side: `user.role` is gone, `user.permissions[]` is the source of UI gating. Force-logout any active admin session that pre-dates the upgrade.
3. **Set new environment variables** in production `.env`:
   - `ADMIN_PERMISSIONS` — comma-separated list of permission codes granting `/admin/*` entry (default: `iam.admin-access`).
   - `SESSION_DRIVER` — `file` / `redis` / `database` / `memcached`. The default falls back to `FileHandler` when unset, but multi-server deployments must set this explicitly.
   - `ASSET_VERSION` — cache-busting token (recommended: `git rev-parse --short HEAD` per deploy). Falls back to file mtime in dev.
   - `LOG_FORMAT=json` to opt into `JsonFileHandler` line-delimited JSON logs. Default text format is preserved when unset.
   - `MAINTENANCE_MODE=true` (plus optional `MAINTENANCE_MESSAGE` and `MAINTENANCE_RETRY_AFTER`) to opt into the short-circuit during deploys.
4. **Port any custom `AdminFilter` whitelist** to `app/Config/AdminAccess.php` (or set `ADMIN_PERMISSIONS` in `.env`). The hardcoded list in the filter source has been removed.
5. **Review custom views** for CSP/Frame-Options/Permissions-Policy compatibility — `SecurityHeadersFilter` is now wired by default. Inline scripts, embedded third-party iframes, and cross-origin script tags may need policy adjustments.
6. **Rebuild the Docker image** with the new multi-stage `Dockerfile` if you were using the previous one. The base image and runtime user (now `www-data`) changed; custom layers must re-base accordingly.
7. **Update any code calling `has_admin_access()`** to `has_permission('iam.admin-access')`. UI templates that referenced `$user['role']` must consume `$user['permissions']`.
8. **Update any extension of `ProfileController::update()`** that sent fields beyond `first_name` / `last_name` / `avatar_url` — those are now silently dropped by the API. Use `PUT /users/{id}` (admin endpoint) if you genuinely need to mutate other fields.
9. **Remove any references to `AppUserMembershipController`** in custom routes, navigation, or templates. Role assignment is via `UserController` (`role_ids[]` payload).
10. **(Optional) BFF health monitoring** — set `bffApiClient.baseUrl = http://localhost:8088` in `.env` to display a BFF health card on the dashboard. Leave unset (or empty) if no BFF gateway is in the stack — no code changes required.
11. **Upgrade Node to `^22.22.1` or `>=24`** before running `npm ci`. `lint-staged@17` requires Node `>=22.22.1`; Node 20 is no longer supported.
12. **Port any `tailwind.config.js` customisations** to `src/css/app.css`. The mapping is:
    - JS `theme.extend.colors` → CSS `@theme { --color-*: ... }`. v4 expects full color values (e.g. `rgb(239 246 255)` or `oklch(...)`), not RGB triplets — the `<alpha-value>` indirection is gone (opacity modifiers like `bg-brand-500/50` now generate `color-mix()` automatically).
    - JS `theme.extend.fontFamily` → CSS `@theme { --font-sans: ...; --font-mono: ... }`.
    - JS `safelist` → CSS `@source inline("class-name")`. The `@source inline()` directive supports brace expansion for ranges.
    - JS `content` paths → CSS `@source "../path"`. v4 auto-detects sources, but explicit `@source` directives are safer for PHP-heavy projects.
    Run `npm run build:css` to regenerate `public/assets/css/app.css`, then smoke-test a couple of representative views (login, dashboard, a striped table) to confirm visual parity.

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

[unreleased]: https://github.com/dcardenasl/ci4-admin-starter/compare/v2.5.0...HEAD
[2.5.0]: https://github.com/dcardenasl/ci4-admin-starter/compare/v2.4.0...v2.5.0
[2.4.0]: https://github.com/dcardenasl/ci4-admin-starter/compare/v2.3.0...v2.4.0
[2.3.0]: https://github.com/dcardenasl/ci4-admin-starter/compare/v2.2.0...v2.3.0
[2.2.0]: https://github.com/dcardenasl/ci4-admin-starter/compare/v2.1.1...v2.2.0
[2.1.1]: https://github.com/dcardenasl/ci4-admin-starter/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/dcardenasl/ci4-admin-starter/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/dcardenasl/ci4-admin-starter/compare/v1.1.0...v2.0.0
[1.1.0]: https://github.com/dcardenasl/ci4-admin-starter/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/dcardenasl/ci4-admin-starter/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/dcardenasl/ci4-admin-starter/releases/tag/v1.0.0
