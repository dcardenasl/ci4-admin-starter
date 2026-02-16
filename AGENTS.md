# Repository Guidelines

## Project Structure & Module Organization
This repository is a CodeIgniter 4 web app starter for an admin frontend.

- `app/`: application code (controllers, config, views, filters, helpers, models).
- `public/`: web root and public assets (`index.php`, `favicon.ico`, static files).
- `system/`: CodeIgniter framework core (do not edit unless intentionally maintaining a framework fork).
- `tests/`: PHPUnit suites (`unit/`, `database/`, `session/`, `_support/`).
- `writable/`: runtime files (logs, cache, sessions, uploads).
- `docs/plan/PLAN-CI4-CLIENT.md`: implementation roadmap for planned modules.

## Build, Test, and Development Commands
- `composer install`: installs PHP dependencies.
- `cp env .env`: creates local environment file.
- `php spark serve --port 8081`: runs the app locally at `http://localhost:8081`.
- `vendor/bin/phpunit`: runs the full test suite.
- `vendor/bin/phpunit --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/`: generates coverage reports.

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
- Test files should end with `Test.php` and use descriptive names (e.g., `HealthTest`).
- Prefer unit tests for helpers/services and integration/database tests when behavior depends on persistence.
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
