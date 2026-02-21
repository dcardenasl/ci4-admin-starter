# Repository Guidelines

## Project Structure & Module Organization
This repository is a CodeIgniter 4 web app starter for an admin frontend. All modules are fully implemented.

- `app/Controllers/`: web controllers (extend `BaseWebController`).
- `app/Services/`: API service classes per domain (extend `BaseApiService`).
- `app/Libraries/`: `ApiClient.php` (HTTP client) and `ApiClientInterface.php`.
- `app/Filters/`: `AuthFilter`, `AdminFilter`, `LocaleFilter`.
- `app/Helpers/`: `ui_helper.php` (view utilities) and `form_helper.php` (field errors).
- `app/Language/`: i18n files for `en` and `es`.
- `app/Config/`: `Routes.php`, `Filters.php`, `Autoload.php`, `ApiClient.php`, `Services.php`.
- `app/Views/`: server-rendered views organized by module.
- `public/`: web root and public assets (`index.php`, `favicon.ico`, static files, `assets/js/app.js`).
- `system/`: CodeIgniter framework core (do not edit unless intentionally maintaining a framework fork).
- `tests/`: PHPUnit suites (`unit/`, `feature/`, `database/`, `session/`, `_support/`).
- `writable/`: runtime files (logs, cache, sessions, uploads).
- `docs/plan/PLAN-CI4-CLIENT.md`: implementation history and architecture reference.
- `docs/COMPATIBILIDAD-API.md`: API compatibility contract (JSON shapes, pagination, filter conventions).

## Build, Test, and Development Commands
- `composer install`: installs PHP dependencies.
- `cp env .env`: creates local environment file.
- `php spark serve --port 8082`: runs the app locally at `http://localhost:8082`.
- `vendor/bin/phpunit`: runs the full test suite (unit + feature).
- `vendor/bin/phpunit tests/unit`: runs only unit tests.
- `vendor/bin/phpunit tests/feature`: runs only feature tests.
- `vendor/bin/phpunit --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/`: generates coverage reports.
- `vendor/bin/php-cs-fixer fix`: auto-fixes code style (PSR-12).
- `vendor/bin/php-cs-fixer fix --dry-run --diff`: previews style changes without applying them.

Run all commands from the repository root.

## Coding Style & Naming Conventions
- Follow PSR-12 and existing CodeIgniter 4 conventions.
- Use 4 spaces for indentation in PHP files.
- Class names: `PascalCase` (e.g., `AuthController`).
- Methods/variables: `camelCase`.
- Config files stay in `app/Config`; route definitions in `app/Config/Routes.php`.
- Keep controllers thin; move API/data logic to service/library classes.

## Testing Guidelines
- Framework: PHPUnit (configured via `phpunit.xml.dist`).
- Test files must end with `Test.php` and use descriptive names (e.g., `AuthFilterTest`).
- `tests/unit/`: test individual classes (libraries, filters, helpers, services, views) with mocks.
- `tests/feature/`: test controller flows end-to-end using CI4's test HTTP layer; enforce filter/auth behavior and table state forwarding.
- Prefer unit tests for pure logic; use feature tests for request/response flows and auth enforcement.
- Add or update tests for every behavior change or bug fix.

## Commit & Pull Request Guidelines
- Use clear, imperative commit messages. Conventional prefixes are recommended (e.g., `feat:`, `fix:`, `chore:`).
- Keep commits focused; avoid mixing refactors with feature changes.
- PRs should include:
  - short summary of what changed and why,
  - linked issue/ticket (if available),
  - testing evidence (`vendor/bin/phpunit` output summary),
  - screenshots for UI changes.

## Security & Configuration Tips
- Never commit secrets (`.env`, tokens, credentials).
- Ensure server `DocumentRoot` points to `public/`.
- Treat `writable/` as runtime-only content; commit only placeholder files when needed.
