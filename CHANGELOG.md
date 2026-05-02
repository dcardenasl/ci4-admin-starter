# Changelog

All notable changes to ci4-admin-starter will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
