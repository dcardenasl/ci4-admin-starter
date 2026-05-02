# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**CI4 Admin Starter** is a CodeIgniter 4 web application (server-rendered frontend) designed to consume the external API from [`ci4-api-starter`](https://github.com/dcardenasl/ci4-api-starter). It provides an administrative panel interface for authentication, user management, file management, audit logs, and metrics.

**Architecture flow:**
```
Browser → CI4 Admin Starter (port 8082) → ci4-api-starter API (port 8080)
```

**Current state:** Fully implemented. All modules are active: authentication, dashboard, profile, file management, and admin panel (users, audit logs, API keys, metrics). See `docs/INDEX.md` for detailed architectural documentation.

## Technology Stack

- **Framework:** CodeIgniter 4 (PHP 8.2+)
- **Rendering:** Server-side PHP views
- **Styling:** Tailwind CSS (CDN-based)
- **Icons:** Lucide Icons (CDN-based)
- **Interactivity:** Alpine.js (CDN-based)
- **Authentication:** JWT tokens stored in PHP sessions (server-side only)
- **HTTP Client:** Custom ApiClient library with automatic token refresh
- **i18n:** CodeIgniter 4 Language files (`en` / `es`)

## Development Commands

### Setup and Installation

**Quick setup (recommended):**
```bash
# Run the interactive setup script
bash install.sh

# This automates:
# - Creating .env from template
# - Configuring CI_ENVIRONMENT, baseURL, apiClient settings
# - Installing Composer and npm dependencies (optional prompts)
# - Updating all template references (API name, port, etc)
```

**Manual setup:**
```bash
# Install PHP dependencies
composer install

# Install npm dependencies (for Tailwind CSS building)
npm install

# Create local environment file
cp env .env

# Edit .env to configure:
# - CI_ENVIRONMENT = development
# - app.baseURL = 'http://localhost:8082/'
# - apiClient.baseUrl = 'http://localhost:8080'
# - apiClient.appKey = apk_... (optional, see API App Key section)
```

### Running the Application

**Terminal 1: Start PHP development server**
```bash
# Start on specific port (recommended for this project)
php spark serve --port 8082
```

**Terminal 2: Watch and rebuild CSS**
```bash
# Start Tailwind CSS watcher
npm run dev:css
```

Application will be available at: `http://localhost:8082`

**Notes:**
- Both terminal sessions should run in parallel during development
- CSS must be built via npm (Tailwind) — the styles are not included in the CDN version used in production
- Production builds use `npm run build:css` to generate minified CSS (see DEPLOYMENT.md)

### Testing
```bash
# Run all tests
vendor/bin/phpunit

# Run tests with coverage reports
vendor/bin/phpunit --colors --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/

# Run specific test directory
vendor/bin/phpunit tests/unit
vendor/bin/phpunit tests/feature

# Run with memory limit (for large test suites)
vendor/bin/phpunit -d memory_limit=1024m
```

### Code Quality

**PHP:**
```bash
# Run PHPStan (static analysis)
vendor/bin/phpstan analyse

# Run PHP CS Fixer (code style)
vendor/bin/php-cs-fixer fix

# Run with dry-run to see what would change
vendor/bin/php-cs-fixer fix --dry-run --diff

# Run full quality check (analyse + format check)
composer quality
```

**JavaScript:**
```bash
# Lint JavaScript files
npm run lint:js

# Lint all JS files (not just app.js)
npm run lint:all
```

**Composer scripts:**
```bash
composer test            # Run all tests (no coverage)
composer test:coverage   # Run tests with coverage report
composer test:unit       # Run unit tests only
composer test:feature    # Run feature tests only
composer analyse         # PHPStan analysis
composer format:check    # Check code style (dry-run)
composer format          # Auto-fix code style
composer quality         # Run analyse + format:check
composer ci              # Full CI suite: tests + quality
```

## Core Architecture Patterns

### ApiClient: Central HTTP Communication Layer

The `app/Libraries/ApiClient.php` class is the heart of all API communication. It handles:

- **All HTTP methods:** `get()`, `post()`, `put()`, `delete()`, `upload()`
- **Public endpoints:** `publicGet()`, `publicPost()` (no authentication)
- **Automatic token refresh:** On 401 responses, attempts to refresh JWT tokens transparently
- **Session-based token storage:** Tokens never exposed to browser
- **App identification:** Sends `X-App-Key` header on every request when `apiClient.appKey` is configured (raises API rate limit from 60 to 600 req/min)

**Auto-refresh flow:**
1. API request returns 401 (token expired)
2. ApiClient automatically calls `POST /api/v1/auth/refresh` with refresh_token from session
3. On success: updates session tokens and retries the original request
4. On failure: destroys session (AuthFilter redirects to /login)

### Authentication & Authorization

**Session storage** (server-side PHP session):
```php
$session->set('access_token', $data['access_token']);
$session->set('refresh_token', $data['refresh_token']);
$session->set('token_expires_at', time() + $data['expires_in']);
$session->set('user', $data['user']); // {id, email, first_name, last_name, avatar_url, role}
```

**Filters:**
- `AuthFilter` (`app/Filters/AuthFilter.php`): Verifies presence of `access_token` in session, redirects to `/login` if missing
- `AdminFilter` (`app/Filters/AdminFilter.php`): Checks `session('user.role') === 'admin'`, redirects to `/dashboard` with error flash if not admin
- `LocaleFilter` (`app/Filters/LocaleFilter.php`): Reads `session('locale')`, validates against supported locales, sets the language for the current request

All filters are registered in `app/Config/Filters.php`. `csrf` and `locale` run globally on every request; `auth` and `admin` are applied per route group.

### Controllers & Base Classes

- **BaseWebController** (`app/Controllers/BaseWebController.php`): Base for all web controllers
  - Provides access to ApiClient instance (`$this->apiClient`)
  - Common view data setup (`appName`, `user`, `currentLocale`, `supportedLocales`)
  - Render helpers: `render(view, data, layout)`, `renderAuth(view, data)`
  - Flash redirect helpers: `withSuccess()`, `withError()`, `withFieldErrors()`
  - Table utilities: `resolveTableState()`, `buildTableApiParams()`, `resolveTablePagination()`
  - API response utilities: `safeApiCall()`, `extractItems()`, `extractData()`, `firstMessage()`
  - Query utilities: `resolveDateRange()`, `positiveIntFromQuery()`

All feature controllers extend BaseWebController, not the framework's BaseController.

### Service Layer Pattern

API communication is abstracted into service classes in `app/Services/`. All services extend `BaseApiService`, which injects `ApiClientInterface` via its constructor.

- `AuthApiService.php` — Authentication endpoints (login, register, forgot/reset password, verify email, profile update, me, resend verification)
- `UserApiService.php` — User management (list, get, create, update, delete, approve)
- `FileApiService.php` — File operations (list, upload, getDownload, delete)
- `AuditApiService.php` — Audit log endpoints (list, get, byEntity)
- `ApiKeyApiService.php` — Admin API key management (list, get, create, update, delete)
- `MetricsApiService.php` — Metrics (summary, timeseries with `/metrics/timeseries` → `/metrics` fallback)
- `HealthApiService.php` — API health check across configured paths; returns `up` / `degraded` / `down` state with latency

Services are registered in `app/Config/Services.php` as shared singletons. Access via `service('authApiService')`, `service('apiClient')`, etc.

### Language / i18n

The app supports English (`en`) and Spanish (`es`). Language files live in `app/Language/{en,es}/`:
`App.php`, `Auth.php`, `Dashboard.php`, `Files.php`, `Users.php`, `Audit.php`, `ApiKeys.php`, `Metrics.php`, `Profile.php`, `Validation.php`

`LocaleFilter` sets the locale per-request from `session('locale')`. Users switch language via `GET /language/set?locale=en` (`LanguageController::set`).

### View Organization

```
app/Views/
├── layouts/
│   ├── app.php                    # Authenticated layout (sidebar + navbar)
│   ├── auth.php                   # Public layout (centered card)
│   └── partials/
│       ├── head.php               # Common <head>: Tailwind CDN, Alpine CDN, Lucide CDN, theme config
│       ├── sidebar.php            # Collapsible navigation sidebar
│       ├── navbar.php             # Top bar with user dropdown and language switcher
│       ├── flash_messages.php     # Toast notifications
│       ├── confirm_modal.php      # Reusable confirmation modal
│       ├── pagination.php         # Page-based pagination component
│       ├── remote_pagination.php  # Cursor/page pagination for server-driven tables
│       ├── filter_panel.php       # Reusable collapsible filter panel wrapper
│       └── table_toolbar.php      # Table toolbar with search and action buttons
├── auth/                          # Login, register, password reset, email verification
├── dashboard/                     # Stats cards, recent files, API health indicator
├── profile/                       # Profile form, change password, resend verification
├── files/                         # File manager: drag-and-drop upload + server-driven table
│   └── partials/                  # filters.php, list_section.php
├── users/                         # Admin: user CRUD (index, show, create, edit)
│   └── partials/                  # filters.php, toolbar_actions.php
├── audit/                         # Admin: audit log (index, show)
│   └── partials/                  # filters.php
├── api_keys/                      # Admin: API key management (index, show, create, edit)
│   └── partials/                  # filters.php, toolbar_actions.php
├── metrics/                       # Admin: metrics dashboard
│   └── partials/                  # filters.php
└── errors/
    └── html/                      # Custom 404, 400, 500 error pages
```

### UI/UX Design System

**Principle:** All branding (colors, fonts, logo) configurable from a single location: `app/Views/layouts/partials/head.php`

**CSS Custom Properties:**
```css
:root {
  --color-brand-50 through --color-brand-900: /* Brand color palette */
  --font-sans: 'Inter', system-ui, ...
  --font-mono: 'JetBrains Mono', ...
  --app-name: 'API Client'
}
```

**Design Rules:**
- NO decorative gradients (solid, clean backgrounds)
- `bg-white` for cards, `bg-gray-50` for main background, `bg-gray-900` for sidebar
- Minimal shadows: only `shadow-sm` on cards
- Border-based separation: `border-gray-200`
- Brand colors only for primary actions: `brand-600` buttons, `brand-50` hover states

**Tailwind CSS Utility Classes** defined in `head.php`:
- `.btn-primary`, `.btn-secondary`, `.btn-danger`
- `.form-input`
- `.card`

**Alpine.js** for client-side interactivity (stores in `public/assets/js/app.js`):
- Sidebar toggle, dropdowns, modals
- Toast notifications (auto-dismiss)
- Drag-and-drop file uploads
- Form enhancements (password strength, search debounce)

## Configuration Files

- `app/Config/Routes.php` — All web routes (public, authenticated, admin)
- `app/Config/Filters.php` — Filter registration and aliases (`auth`, `admin`, `locale`)
- `app/Config/Autoload.php` — Helper auto-loading (`ui`, `form` loaded globally)
- `app/Config/ApiClient.php` — API base URL, timeouts, API prefix, app name, `appKey` (reads `API_APP_KEY` env var)
- `app/Config/Services.php` — Shared service factory for `apiClient`, `authApiService`, `fileApiService`, `userApiService`, `auditApiService`, `apiKeyApiService`, `metricsApiService`, `healthApiService`

## API App Key (`X-App-Key`)

The API supports an optional `X-App-Key` header that identifies the admin app as a trusted client and raises the rate limit from 60 to 600 req/min. Configure it in `.env`:

```dotenv
# Create a key via the admin panel at /admin/api-keys
# or via POST /api/v1/api-keys (requires admin JWT).
# Omit to use IP-based rate limiting (60 req/min) — safe default.
# apiClient.appKey = apk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

When present, `ApiClient` injects `X-App-Key` on every request (public and authenticated). When absent, the header is not sent. Configuring an invalid key causes every request to return `401` from the API — a misconfiguration that is caught immediately.

## Implemented Modules

All modules are fully implemented:

| Module | Controller | Key Routes |
|--------|-----------|------------|
| Auth | `AuthController` | `GET/POST /login`, `/register`, `/forgot-password`, `/reset-password`, `GET /verify-email`, `/logout` |
| Dashboard | `DashboardController` | `GET /dashboard` |
| Profile | `ProfileController` | `GET/POST /profile`, `POST /profile/change-password`, `POST /profile/resend-verification` |
| Files | `FileController` | `GET /files`, `/files/data`, `POST /files/upload`, `GET /files/{id}/download`, `POST /files/{id}/delete` |
| Users (admin) | `UserController` | Full CRUD + approve under `/admin/users` |
| Audit (admin) | `AuditController` | `GET /admin/audit`, `/admin/audit/{id}`, `/admin/audit/entity/{type}/{id}` |
| API Keys (admin) | `ApiKeyController` | Full CRUD under `/admin/api-keys` |
| Metrics (admin) | `MetricsController` | `GET /admin/metrics` |
| Language | `LanguageController` | `GET /language/set` |

## File Locations & Patterns

### Module-Based Organization

The project uses a **modular architecture** where each feature is self-contained in `app/Modules/{ModuleName}/`:

**Per-module structure:**
- Controllers: `app/Modules/{ModuleName}/Controllers/` (extend BaseWebController)
- Services: `app/Modules/{ModuleName}/Services/` (extend BaseApiService, implement interfaces)
- Requests: `app/Modules/{ModuleName}/Requests/` (form validation, extend BaseFormRequest)
- Language: `app/Modules/{ModuleName}/Language/{en,es}/` (module-specific language strings)
- Config: `app/Modules/{ModuleName}/Config/Routes.php` (module routes)

**Module list:**
- `app/Modules/Auth/` — Authentication (login, register, password reset, email verification)
- `app/Modules/Dashboard/` — Admin dashboard with statistics
- `app/Modules/Profile/` — User profile and password management
- `app/Modules/Files/` — File upload, download, and management
- `app/Modules/Users/` — User CRUD (admin-only)
- `app/Modules/Audit/` — Audit logs (admin-only)
- `app/Modules/ApiKeys/` — API key management (admin-only)
- `app/Modules/Metrics/` — Metrics and analytics dashboards (admin-only)
- `app/Modules/Language/` — Internationalization (locale switching)

**Scaffolding contract — collision rejection (`bin/make-module.sh` / `bin/remove-module.sh`):**

- Resource names whose `to_camel` form collides with an existing service factory key in `app/Config/Services.php` (e.g. `APIKey` → `apiKeyApiService`, already shipped by `App\Modules\ApiKeys`) are rejected. `make-module.sh` runs a case-insensitive sibling scan against the planned target paths and aborts before writing if any sibling on disk differs only in case (the macOS HFS+/APFS or Windows NTFS scenario where `[[ -f ... ]]` would silently shadow another module's file). `bin/register-service.php` resolves the existing factory's return-type FQCN against the new module's FQCN and refuses to skip silently when they differ (exit 4).
- `remove-module.sh` enforces the symmetric guard: it refuses to delete any file whose `namespace` declaration does not start with `App\Modules\{Module}\` (test files must reference the target module via a `use App\Modules\{Module}\…` clause), and refuses to un-register a factory whose return-type FQCN lives outside `App\Modules\{Module}\Services\` (exit 3 from the embedded helper).
- When the rejection fires, prefer renaming the resource to its canonical StudlyCase form (e.g. `APIKey` → `ApiKey`) before retrying, or remove the conflicting module first.

### Shared Infrastructure (Outside Modules)

- Views: `app/Views/{module_name}/` (organized by module, not inside `app/Modules/`)
- Models: `app/Models/` (not used; all data comes from external API)
- Libraries: `app/Libraries/` (`ApiClient.php`, `ApiClientInterface.php`)
- Filters: `app/Filters/` (`AuthFilter`, `AdminFilter`, `LocaleFilter`)
- Helpers: `app/Helpers/` (`ui_helper.php` for view utilities, `form_helper.php` for field error rendering)
- Language: `app/Language/{en,es}/` (global strings, app-wide messages)
- Config: `app/Config/` (`Routes.php`, `Filters.php`, `Autoload.php`, `ApiClient.php`, `Services.php`)
- Tests: `tests/unit/` (libraries, filters, helpers, services, views) and `tests/feature/` (controller flows)

## Security Considerations

- JWT tokens MUST ONLY be stored in PHP sessions, never in cookies/localStorage accessible by JavaScript
- CSRF protection enabled by default in CodeIgniter 4
- Input validation required on all form submissions
- Admin routes MUST use both `auth` and `admin` filters
- File uploads validated by size (max 10 MB) before being passed to API
- API app key stored only in `.env`; never exposed to client-side code
- Never commit `.env` files or expose API URLs/secrets in client-side code

## External API Reference

This app consumes **ci4-api-starter** (https://github.com/dcardenasl/ci4-api-starter) which provides:
- REST API endpoints for auth, users, files, audit logs, metrics, and API keys
- JWT-based authentication (access + refresh tokens)
- Optional `X-App-Key` header for app-level rate limiting (600 req/min vs 60 req/min)
- Runs on `http://localhost:8080` by default

## Testing Strategy

- **Unit tests** in `tests/unit/`:
  - `Libraries/ApiClientTest.php` — interface contract and config defaults
  - `Filters/AuthFilterTest.php` — redirect when no token / allow when token present
  - `Helpers/UiHelperTest.php` — `has_active_filters()` logic
  - `Services/AuthApiServiceTest.php`, `ApiKeyApiServiceTest.php`, `HealthApiServiceTest.php`, `MetricsApiServiceTest.php`
  - `Views/ErrorViewsSmokeTest.php` — 404/500 error page rendering
- **Feature tests** in `tests/feature/`:
  - `FileUploadFlowTest.php` — upload, download, delete, auth protection, data endpoint
  - `UserCreationInvitationFlowTest.php` — invitation-based create/update (no password field)
  - `ApiKeyFlowTest.php` — admin CRUD flow, auth/admin filter enforcement
  - `ApiKeyFiltersFallbackTest.php`, `AuditFiltersFallbackTest.php`, `FileFiltersFallbackTest.php`, `UserFiltersFallbackTest.php` — table filter/sort/pagination forwarding
  - `ErrorPagesTest.php` — 404 (guest and authenticated) and 500 page rendering
- Coverage reports generated in `tests/coverage/`
- Test configuration: `phpunit.xml.dist` (copy to `phpunit.xml` to customize)

## Common Pitfalls

- **DocumentRoot must point to `public/`**, not the repository root
- `writable/` directory must be writable by the web server user
- Session configuration required for JWT token storage
- CORS may need configuration if API and frontend are on different origins
- Ensure both ci4-api-starter and ci4-admin-starter are running on different ports during development
- Configuring an invalid `apiClient.appKey` causes every API request to return `401` — omit the key rather than use a wrong value

## References

- **Documentation Hub:** `docs/INDEX.md` — Complete guides for architecture, frontend, services, testing, deployment, and how-to guides
- **Architecture Deep Dive:** `docs/ARCHITECTURE.md` — ApiClient, security patterns, and data flow
- **API Compatibility Contract:** `docs/API-COMPATIBILITY.md` — Mandatory backend/frontend integration rules
- **Services & Validation:** `docs/SERVICES.md` — Service layer pattern and FormRequest validation
- **Testing Guide:** `tests/README.md` and `docs/TESTING.md` — Unit/feature test strategies
- **Frontend Guidelines:** `docs/FRONTEND.md` — UI/UX patterns, Tailwind, Alpine.js
- **Deployment Guide:** `docs/DEPLOYMENT.md` — Production checklist and server configuration
- **How-To Guides:** `docs/HOW-TO.md` — Step-by-step instructions for common tasks
- [CodeIgniter 4 User Guide](https://codeigniter.com/user_guide/)
- [CI4 API Starter Repository](https://github.com/dcardenasl/ci4-api-starter)
