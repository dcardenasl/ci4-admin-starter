# GEMINI.md

## Project Overview

**CI4 Admin Starter** is a PHP 8.1+ web application built with **CodeIgniter 4**. It serves as a server-rendered administrative frontend designed to consume an external REST API (specifically `ci4-api-starter`). 

The project follows a decoupled architecture where the frontend handles UI, session management, and request orchestration, while business logic and data persistence reside in the backend API.

### Key Technologies
- **Framework:** CodeIgniter 4
- **Runtime:** PHP 8.1+
- **Styling:** Tailwind CSS (via CDN)
- **Interactivity:** Alpine.js (via CDN)
- **Icons:** Lucide Icons (via CDN)
- **Authentication:** JWT (stored in server-side PHP sessions)
- **HTTP Client:** Custom `ApiClient` with automatic token refresh

### Architecture Flow
`Browser -> CI4 Admin Starter (this repo) -> Backend API (ci4-api-starter)`

---

## Building and Running

### Prerequisites
- PHP ^8.1
- Composer 2.x
- PHP Extensions: `intl`, `mbstring`, `curl` (recommended)

### Setup
```bash
# Install dependencies
composer install

# Create environment configuration
cp env .env
```

**Required `.env` settings:**
- `CI_ENVIRONMENT = development`
- `app.baseURL = 'http://localhost:8082/'`
- `apiClient.baseUrl = 'http://localhost:8080'` (Address of the backend API)

### Running the Project
```bash
# Start development server
php spark serve --port 8082
```
The application will be available at `http://localhost:8082`.

---

## Testing and Quality

### Running Tests
```bash
# Run all tests
vendor/bin/phpunit

# Run tests with coverage
vendor/bin/phpunit --colors --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/
```

### Coding Style
The project follows PSR-12/CodeIgniter coding standards.
```bash
# Fix code style automatically
vendor/bin/php-cs-fixer fix
```

---

## Development Conventions

### 1. ApiClient Layer
All external communication must go through `app/Libraries/ApiClient.php`. It handles authentication headers, error normalization, and transparent JWT token refresh.

### 2. Service Layer
Business-domain API calls are encapsulated in `app/Services/`. Every service should extend `BaseApiService` and be registered in `app/Config/Services.php`.

### 3. Validation (FormRequest)
Validation logic is centralized in `app/Requests/` to keep controllers thin. 
- Use `service('formRequest', RequestClass::class)` to instantiate.
- Controllers should use `$this->validateRequest($request)` for uniform error handling.

### 4. Controller Pattern
All web controllers must extend `App\Controllers\BaseWebController`.
- Use `render()` for authenticated views.
- Use `renderAuth()` for public/auth views.
- Leverage built-in helpers for flash messages and table state management.

### 5. Authentication & Filters
- `AuthFilter`: Protects routes requiring a valid session.
- `AdminFilter`: Restricts access to users with the 'admin' role.
- `LocaleFilter`: Manages multi-language support (English/Spanish).

### 6. Internationalization (i18n)
Language strings are stored in `app/Language/`. Always use the `lang()` helper in views and controllers to support both `en` and `es`.

---

## Key Directories
- `app/Controllers/`: Request handling and view orchestration.
- `app/Services/`: API domain logic.
- `app/Requests/`: Form validation rules and payload normalization.
- `app/Views/`: PHP/Tailwind/Alpine templates.
- `app/Libraries/`: Core utilities (`ApiClient`, etc.).
- `docs/`: Technical documentation including API compatibility and implementation plans.
