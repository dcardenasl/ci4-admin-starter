# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**CI4 Admin Starter** is a CodeIgniter 4 web application (server-rendered frontend) designed to consume the external API from [`ci4-api-starter`](https://github.com/dcardenasl/ci4-api-starter). It provides an administrative panel interface for authentication, user management, file management, audit logs, and metrics.

**Architecture flow:**
```
Browser → CI4 Admin Starter (port 8081) → ci4-api-starter API (port 8080)
```

**Current state:** Fresh CodeIgniter 4 scaffold. Only the default `/` route is active. Core modules (auth, dashboard, profile, files, admin) are NOT yet implemented. See `docs/plan/PLAN-CI4-CLIENT.md` for the complete implementation roadmap.

## Technology Stack

- **Framework:** CodeIgniter 4 (PHP 8.1+)
- **Rendering:** Server-side PHP views
- **Styling:** Tailwind CSS (CDN-based)
- **Interactivity:** Alpine.js (CDN-based)
- **Authentication:** JWT tokens stored in PHP sessions (server-side only)
- **HTTP Client:** Custom ApiClient library with automatic token refresh

## Development Commands

### Setup and Installation
```bash
# Install dependencies
composer install

# Create local environment file
cp env .env

# Edit .env to configure:
# - CI_ENVIRONMENT = development
# - app.baseURL = 'http://localhost:8081/'
# - API_BASE_URL (when ApiClient is implemented)
```

### Running the Application
```bash
# Start development server (default port 8080)
php spark serve

# Start on specific port (recommended for this project)
php spark serve --port 8081
```

Application will be available at: `http://localhost:8081`

### Testing
```bash
# Run all tests
vendor/bin/phpunit

# Run tests with coverage reports
vendor/bin/phpunit --colors --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/

# Run specific test directory
vendor/bin/phpunit tests/unit

# Run with memory limit (for large test suites)
vendor/bin/phpunit -d memory_limit=1024m
```

### Code Quality
```bash
# Run PHP CS Fixer (code style)
vendor/bin/php-cs-fixer fix

# Run with dry-run to see what would change
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Core Architecture Patterns

### ApiClient: Central HTTP Communication Layer

The `app/Libraries/ApiClient.php` class is the heart of all API communication. It handles:

- **All HTTP methods:** `get()`, `post()`, `put()`, `delete()`, `upload()`
- **Public endpoints:** `publicGet()`, `publicPost()` (no authentication)
- **Automatic token refresh:** On 401 responses, attempts to refresh JWT tokens transparently
- **Session-based token storage:** Tokens never exposed to browser

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
- `AdminFilter` (`app/Filters/AdminFilter.php`): Checks `session('user.role') === 'admin'`, returns 403 if not admin

Both filters are registered in `app/Config/Filters.php` and applied via route configuration.

### Controllers & Base Classes

- **BaseWebController** (`app/Controllers/BaseWebController.php`): Base for all web controllers
  - Provides access to ApiClient instance
  - Common view data setup
  - Helper method loading

All feature controllers extend BaseWebController, not the framework's BaseController.

### Service Layer Pattern

API communication is abstracted into service classes in `app/Services/`:
- `AuthApiService.php` - Authentication endpoints
- `UserApiService.php` - User management endpoints
- `FileApiService.php` - File operations endpoints
- `AuditApiService.php` - Audit log endpoints
- `MetricsApiService.php` - Metrics endpoints

Each service uses the ApiClient library internally and provides domain-specific methods.

### View Organization

```
app/Views/
├── layouts/
│   ├── app.php              # Authenticated layout (sidebar + navbar)
│   ├── auth.php             # Public layout (centered card)
│   └── partials/
│       ├── head.php         # Common <head>: Tailwind CDN, Alpine CDN, theme config
│       ├── sidebar.php      # Collapsible navigation sidebar
│       ├── navbar.php       # Top bar with user dropdown
│       ├── flash_messages.php  # Toast notifications
│       ├── confirm_modal.php   # Reusable confirmation modal
│       └── pagination.php      # Pagination component
├── auth/                    # Login, register, password reset, email verification
├── dashboard/               # Dashboard views
├── profile/                 # User profile management
├── files/                   # File manager interface
├── users/                   # Admin: user CRUD
├── audit/                   # Admin: audit logs
└── metrics/                 # Admin: metrics dashboard
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

- `app/Config/Routes.php` - All web routes (public, authenticated, admin)
- `app/Config/Filters.php` - Filter registration and aliases
- `app/Config/Autoload.php` - Helper auto-loading (e.g., `ui_helper`)
- `app/Config/ApiClient.php` - API base URL, timeouts, HTTP client settings (when implemented)

## Planned Implementation Phases

See `docs/plan/PLAN-CI4-CLIENT.md` for detailed breakdown. Summary:

**Core phases (initial scope):**
1. Infrastructure core: ApiClient, filters, base controller, helpers
2. API service layer: AuthApiService, FileApiService
3. Layouts and shared components
4. Authentication module: login, register, password reset, email verification
5. Dashboard, profile, and file management

**Future phases:**
6. Admin: User management CRUD
7. Admin: Audit log viewer
8. Admin: Metrics dashboard
9. Error pages and polish

## File Locations & Patterns

- Controllers: `app/Controllers/` (extend BaseWebController)
- Models: `app/Models/` (not heavily used; API is external)
- Views: `app/Views/` (organized by feature module)
- Libraries: `app/Libraries/` (e.g., ApiClient)
- Services: `app/Services/` (API communication abstraction)
- Filters: `app/Filters/` (auth, admin, etc.)
- Helpers: `app/Helpers/` (e.g., `ui_helper.php` for view utilities)
- Config: `app/Config/`
- Tests: `tests/` (unit, database, session subdirectories)

## Security Considerations

- JWT tokens MUST ONLY be stored in PHP sessions, never in cookies/localStorage accessible by JavaScript
- CSRF protection enabled by default in CodeIgniter 4
- Input validation required on all form submissions
- Admin routes MUST use both `auth` and `admin` filters
- File uploads require validation (type, size, etc.)
- Never commit `.env` files or expose API URLs/secrets in client-side code

## External API Reference

This app consumes **ci4-api-starter** (https://github.com/dcardenasl/ci4-api-starter) which provides:
- 35 REST API endpoints
- JWT-based authentication (access + refresh tokens)
- User management, file storage, audit logs, metrics
- Runs on `http://localhost:8080` by default

## Testing Strategy

- Unit tests in `tests/unit/`
- Database tests in `tests/database/` (use migrations/seeds if needed)
- Session tests in `tests/session/`
- Coverage reports generated in `tests/coverage/`
- Test configuration: `phpunit.xml.dist` (copy to `phpunit.xml` to customize)

## Common Pitfalls

- **DocumentRoot must point to `public/`**, not the repository root
- `writable/` directory must be writable by the web server user
- Session configuration required for JWT token storage
- CORS may need configuration if API and frontend are on different origins
- Ensure both ci4-api-starter and ci4-admin-starter are running on different ports during development

## References

- [CodeIgniter 4 User Guide](https://codeigniter.com/user_guide/)
- [CI4 API Starter Repository](https://github.com/dcardenasl/ci4-api-starter)
- Implementation Plan: `docs/plan/PLAN-CI4-CLIENT.md`
- Testing Guide: `tests/README.md`
