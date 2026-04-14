# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Type safety improvements: `declare(strict_types=1)` in all FormRequest classes
- `@param array<string, mixed>` type hints in all service interfaces
- `auth_helper.php` with authentication utility functions (`has_admin_access()`, `is_email_verified()`)
- `.github/dependabot.yml` for automated dependency updates
- `.github/PULL_REQUEST_TEMPLATE.md` with contribution checklist
- `.github/ISSUE_TEMPLATE/` for bug reports and feature requests
- Cache support in `.github/workflows/ci.yml` for faster CI builds
- `composer audit` step in CI pipeline for security scanning
- `CatalogApiServiceInterface` for interface-based dependency injection
- Field validation for `original_email` in `UserUpdateRequest`

### Changed
- Removed aliases: `HealthApiService` now directly references `App\Modules\Dashboard\Services\HealthApiService`
- Enhanced `.php-cs-fixer.dist.php` with explicit PSR12 rules and modernization features
- Updated `phpunit.xml.dist` with `pathCoverage="true"` and `minimumCoverage="80"`
- `package.json`: Added `engines` requirement for Node >=20.0.0 and new lint scripts
- `.env.example`: Added clear **REQUIRED** vs **OPTIONAL** sections and encryption key documentation
- Regenerated PHPStan baseline: 146 → 131 errors (10% reduction through type safety improvements)

### Fixed
- DashboardController now uses `FileApiServiceInterface` and `UserApiServiceInterface` instead of concrete classes
- Removed empty `/app/Modules/Language/Language/en` and `/es` directories

### Security
- `composer audit` now runs in CI pipeline to detect vulnerable dependencies
- Improved environment variable documentation for critical configuration

### Documentation
- Updated `.env.example` with comprehensive section headers
- Added guidance for `encryption.key` generation via `php spark key:generate`
- Documented optional email/SMTP configuration
