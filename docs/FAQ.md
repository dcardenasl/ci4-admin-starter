# Frequently Asked Questions (FAQ)

Common questions about **CI4 Admin Starter** development and deployment.

## General Questions

### What is CI4 Admin Starter?

**CI4 Admin Starter** is a production-ready administrative dashboard template built with CodeIgniter 4. It's designed to consume a backend API (`ci4-api-starter`) and provide a server-rendered user interface for managing data, users, files, and more.

Key features:
- Server-rendered views (PHP + Tailwind CSS)
- JWT authentication with automatic token refresh
- Service layer for clean API communication
- FormRequest validation pattern
- Built-in modules (Auth, Dashboard, Users, Files, Audit, Metrics, API Keys)
- i18n support (English & Spanish)
- Comprehensive test suite

### Is this a SPA (Single Page Application)?

No. This is a **Server-Rendered Frontend (SRF)**. Every request goes to the PHP server, which handles routing, validation, and view rendering. The frontend and backend are separate applications communicating via HTTP/REST API.

Advantages:
- Simpler architecture for team collaboration
- SEO-friendly (if needed)
- Session-based JWT storage (more secure than localStorage)
- No complex JavaScript build tooling

### Can I use this for a non-admin application?

Yes, but it's optimized for admin interfaces. The design patterns and included modules (Users, Audit Logs, API Keys) are typical for admin panels.

For general-purpose applications, you might:
- Remove unnecessary modules
- Customize the UI to match your brand
- Add your own domain-specific modules

See [How-To Guides](./HOW-TO.md) for examples.

### Is this suitable for production?

Yes. The project includes:
- ✅ Comprehensive test coverage (unit + feature tests)
- ✅ Security best practices (CSRF, CSP, secure session storage)
- ✅ Error handling and logging
- ✅ Performance optimizations (caching, efficient queries)
- ✅ Production deployment checklist in [DEPLOYMENT.md](./DEPLOYMENT.md)

Follow the production checklist before deploying.

---

## Architecture & Design

### Why server-rendered and not a SPA?

Server-rendered pages are simpler to build, maintain, and understand. They reduce the complexity of frontend tooling and state management. For admin interfaces, the performance difference is negligible.

SPA would require:
- Complex bundling setup (Webpack, Vite, etc.)
- Client-side state management (Redux, Zustand)
- Complex error handling across network boundaries
- Frontend deployment considerations

Server-rendered keeps the architecture straightforward.

### How does authentication work?

1. **User logs in** → Backend API validates credentials and returns JWT tokens
2. **Frontend stores tokens** in server-side PHP session (never in localStorage/cookies)
3. **On API calls** → Token is read from session and sent in `Authorization: Bearer <token>` header
4. **If token expires** → ApiClient automatically calls refresh endpoint and retries the request
5. **On logout** → Session is destroyed, user redirected to login

This approach is more secure than storing tokens in the browser.

### What is the FormRequest pattern?

FormRequest is a validation layer that centralizes form validation in dedicated classes within each module (`app/Modules/{ModuleName}/Requests/*Request.php`).

Benefits:
- Controllers stay thin and focused
- Validation rules reusable across endpoints
- Automatic error formatting
- Payload normalization (type conversion, field mapping)

Example:
```php
// app/Modules/Users/Requests/UserStoreRequest.php
class UserStoreRequest extends BaseFormRequest {
    public function rules(): array {
        return [
            'email' => 'required|valid_email',
            'first_name' => 'required|max_length[255]',
        ];
    }
    
    public function payload(): array {
        return [
            'email' => $this->getPost('email'),
            'first_name' => trim($this->getPost('first_name')),
        ];
    }
}

// In controller (UserController)
$request = service('formRequest', UserStoreRequest::class, false);
$invalid = $this->validateRequest($request);
if ($invalid) return $invalid;

$response = $this->safeApiCall(
    fn() => $this->userService->create($request->payload())
);
```

### How is the Service layer organized?

Services encapsulate API communication for a specific domain. Each module has its own services:

```
app/Modules/
├── Auth/Services/
│   ├── AuthApiService.php                # Authentication endpoints
│   └── AuthApiServiceInterface.php       # Interface contract
├── Users/Services/
│   ├── UserApiService.php                # User management endpoints
│   └── UserApiServiceInterface.php       # Interface contract
├── Files/Services/
│   ├── FileApiService.php                # File management endpoints
│   └── FileApiServiceInterface.php       # Interface contract
└── ... (more modules)
```

All services extend `BaseApiService` (in `app/Services/`) and are registered in `app/Config/Services.php` as shared singletons.

**Pattern:**
1. Controller calls a Service method
2. Service uses `$this->apiClient` to make HTTP request
3. Service returns normalized response
4. Controller handles the response and renders view

This separation keeps concerns distinct.

---

## Development Questions

### How do I add a new feature?

See [How-To Guides](./HOW-TO.md) for detailed instructions. Generally:

1. **Create a new Module** in `app/Modules/{ModuleName}/`
2. **Create FormRequest classes** in `app/Modules/{ModuleName}/Requests/` with validation rules
3. **Create/extend a Service** in `app/Modules/{ModuleName}/Services/` to call the API
4. **Create a Controller** in `app/Modules/{ModuleName}/Controllers/` that uses the Service
5. **Add routes** to `app/Modules/{ModuleName}/Config/Routes.php`
6. **Create views** in `app/Views/{module_name}/`
7. **Add language strings** to `app/Modules/{ModuleName}/Language/{en,es}/{ModuleName}.php`
8. **Write tests** in `tests/unit/` and `tests/feature/`

Example: [How-To: Add a New Module](./HOW-TO.md#add-a-new-module)

### How do I customize the UI?

The design system is centralized in `app/Views/layouts/partials/head.php`:

```html
<style>
    :root {
        --color-brand-50: #f0f9ff;     /* Update these */
        --color-brand-500: #0284c7;
        --color-brand-600: #0369a1;
        --app-name: 'Your App Name';
    }
</style>
```

Also update:
1. **Logo** in `app/Views/layouts/partials/sidebar.php`
2. **App name** in `app/Config/App.php` (`appName` setting)
3. **Colors** via CSS custom properties

See [Frontend Guide](./FRONTEND.md) for component patterns.

### How do I change the sidebar navigation?

The sidebar is defined in `app/Views/layouts/partials/sidebar.php`.

Add or remove menu items:
```php
<a href="<?= base_url('/admin/users') ?>" class="sidebar-link">
    <svg class="lucide-icon" data-icon="users"></svg>
    <span><?= lang('App.users') ?></span>
</a>
```

Use Lucide icon names from https://lucide.dev/icons/

### How do I add internationalization (i18n)?

The project supports English (`en`) and Spanish (`es`).

**To add a new language:**
1. Create `app/Language/fr/` (example: French)
2. Copy language files from `app/Language/en/`
3. Translate the content
4. Update `app/Filters/LocaleFilter.php` to include `'fr'`
5. Add language switcher option in navbar

**To add a new language string:**
1. Add to appropriate file: `app/Language/en/Users.php`
   ```php
   return [
       'create_user' => 'Create User',
   ];
   ```
2. Use in views: `<?= lang('Users.create_user') ?>`

### How do I handle API errors in my code?

Use the `safeApiCall()` helper from `BaseWebController`:

```php
$response = $this->safeApiCall(
    fn() => $this->userService->findById($id)
);

if (!$response['ok']) {
    return $this->failApi($response, 'User not found');
}

$user = $this->extractData($response);
```

This automatically handles:
- Network errors and exceptions
- API error responses
- Field validation errors
- Message extraction and formatting

---

## Testing Questions

### How do I run tests?

```bash
# Run all tests
composer test

# Run specific test suite
composer test:unit
composer test:feature

# Run with coverage report
composer test:coverage

# Run specific test file
vendor/bin/phpunit tests/unit/Services/UserApiServiceTest.php

# Run with verbose output
vendor/bin/phpunit --verbose
```

All tests should pass before committing.

### How do I mock the ApiClient in tests?

The framework auto-mocks the ApiClient in test environment. Example:

```php
namespace Tests\Unit\Services;

use Tests\Support\TestCase;

class UserApiServiceTest extends TestCase {
    public function testFindUserById() {
        // The test container provides a mock ApiClient
        $this->mockApiCall('get', '/api/v1/users/1', [
            'ok' => true,
            'data' => ['id' => 1, 'email' => 'test@example.com']
        ]);
        
        $service = service('usersApi');
        $result = $service->findById(1);
        
        $this->assertTrue($result['ok']);
    }
}
```

See [Testing Guide](./TESTING.md) for more examples.

### How do I test file uploads?

Feature tests can simulate file uploads:

```php
public function testFileUpload() {
    $file = $this->createTestFile('test.pdf', 'application/pdf');
    
    $response = $this->post('/files/upload', [
        'file' => $file,
    ]);
    
    $response->assertStatus(200);
}
```

See `tests/feature/FileUploadFlowTest.php` for full example.

### How do I write integration tests with the real API?

For integration testing against a real backend:

```php
// In phpunit.xml, set:
<env name="API_URL" value="http://localhost:8180"/>

// In test:
public function testLoginWithRealAPI() {
    // Makes real HTTP call to backend
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password'
    ]);
    
    $response->assertStatus(302);  // Redirect to dashboard
}
```

**Note:** This requires backend to be running. Better for CI/CD pipelines.

---

## Deployment Questions

### How do I deploy to production?

See the complete [Deployment Guide](./DEPLOYMENT.md).

Quick checklist:
```bash
# 1. Set environment variables
CI_ENVIRONMENT = production
app.baseURL = 'https://yourdomain.com'
app.forceGlobalSecureRequests = true
app.CSPEnabled = true
cookie.secure = true

# 2. Install dependencies (production only)
composer install --no-dev --optimize-autoloader

# 3. Build CSS
npm ci && npm run build:css

# 4. Run tests one final time
composer quality

# 5. Set correct file permissions
chmod -R 755 writable/
chmod -R 755 public/

# 6. Deploy to server
# (Git push, Docker, manual upload, etc.)
```

### How do I configure HTTPS and security headers?

In `.env`:
```dotenv
app.baseURL = 'https://yourdomain.com'
app.forceGlobalSecureRequests = true
app.CSPEnabled = true
cookie.secure = true
cookie.httpOnly = true
```

Then ensure your web server (Nginx/Apache) is configured with:
- Valid SSL certificate
- Strict-Transport-Security header
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN

See [Deployment Guide](./DEPLOYMENT.md) for server configuration examples.

### How do I handle environment-specific configuration?

Use `.env` files with environment variables:

```dotenv
# .env.local (never commit)
CI_ENVIRONMENT = development
apiClient.baseUrl = http://localhost:8180
apiClient.appKey = apk_dev_key_only

# Production (via CI/CD or server config)
CI_ENVIRONMENT = production
apiClient.baseUrl = https://api.yourdomain.com
apiClient.appKey = apk_prod_key_only
```

Access in code:
```php
config('ApiClient')->baseUrl;
config('App')->environment;
```

### How do I monitor application health?

The project includes a health check endpoint:

```bash
# Check API connectivity
curl http://localhost:8182/health

# Returns JSON status:
{
    "status": "up",           // or "degraded" / "down"
    "services": {
        "api": {
            "status": "up",
            "latency_ms": 45
        }
    }
}
```

Use this endpoint for monitoring dashboards (DataDog, New Relic, etc.).

---

## API Integration Questions

### What API endpoints do I need to implement in my backend?

The project expects a backend following [ci4-api-starter](https://github.com/dcardenasl/ci4-api-starter) contract:

**Required Endpoints:**
```
POST   /api/v1/auth/login
POST   /api/v1/auth/refresh
POST   /api/v1/auth/logout
POST   /api/v1/auth/register
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
POST   /api/v1/auth/verify-email
GET    /api/v1/auth/me

GET    /api/v1/users
GET    /api/v1/users/{id}
POST   /api/v1/users
PUT    /api/v1/users/{id}
DELETE /api/v1/users/{id}
POST   /api/v1/users/{id}/approve

GET    /api/v1/files
POST   /api/v1/files/upload
GET    /api/v1/files/{id}/download
DELETE /api/v1/files/{id}

GET    /api/v1/audit
GET    /api/v1/audit/{id}
GET    /api/v1/audit/entity/{type}/{id}

GET    /api/v1/metrics
GET    /api/v1/metrics/timeseries
```

See [API Compatibility Guide](./API-COMPATIBILITY.md) for complete specification.

### Can I use a different backend API?

Yes, but you'll need to adapt the Services. The frontend expects:

1. **JWT authentication** with access + refresh tokens
2. **Standard JSON response format** with canonical `status`, `message`, `data`, `errors` fields
3. **Consistent error handling** with field-level errors for validation
4. **Optional** `X-App-Key` header support for rate limiting

If your API follows these patterns, you can:
- Update `app/Config/ApiClient.php` with correct URLs
- Modify Services to match your API endpoints
- Update form validation in FormRequests

### How do I handle API authentication with X-App-Key?

The header is automatically injected when configured:

```dotenv
# In .env
apiClient.appKey = apk_xxxxxxxxxxxxxxxx
```

The ApiClient will add `X-App-Key: apk_xxx` to every request automatically.

**Note:** If the key is invalid, every request returns 401. Either use a correct key or omit the setting entirely.

---

## Module-Specific Questions

### How do I remove a module I don't need?

Example: removing the "Metrics" module.

1. **Delete the module folder**:
   ```bash
   rm -rf app/Modules/Metrics/
   ```

2. **Remove from `app/Config/Services.php`**:
   ```php
   // Remove the metricsApi service factory definition
   ```

3. **Remove routes from `app/Config/Routes.php`**:
   ```php
   // Find and remove the metrics module routes
   // Usually something like:
   // service('routes')->module('Metrics', ['namespace' => 'App\Modules\Metrics\Controllers']);
   ```

4. **Remove from sidebar** in `app/Views/layouts/partials/sidebar.php`:
   ```html
   <!-- Remove the metrics menu item/link -->
   ```

5. **Run tests** to ensure nothing broke:
   ```bash
   composer test
   ```

**Note:** Each module is self-contained in `app/Modules/{ModuleName}/` with its own Controllers, Services, Requests, Views, Routes, and Language files.

### How do I customize the File Manager?

The file manager is in `app/Views/files/`. You can:

1. **Change max upload size:**
   ```dotenv
   # .env
   FILE_MAX_SIZE = 20971520  # 20 MB instead of 10 MB
   ```

2. **Change allowed file types:**
   Edit `app/Requests/File/UploadRequest.php`:
   ```php
   'file' => 'uploaded[file]|max_size[file,20971520]|mime_in[file,image/jpeg,application/pdf]'
   ```

3. **Customize UI:**
   Edit `app/Views/files/index.php` and `app/Views/files/partials/list_section.php`

---

## Troubleshooting & Support

### My question isn't answered here

Check these resources:

1. **[Troubleshooting Guide](./TROUBLESHOOTING.md)** — Common issues and solutions
2. **[Quick Start Guide](./QUICK-START.md)** — Setup verification
3. **[How-To Guides](./HOW-TO.md)** — Step-by-step feature development
4. **[Documentation Hub](./INDEX.md)** — All guides organized by topic
5. **[Architecture Guide](./ARCHITECTURE.md)** — System design details

### How do I report a bug?

1. Check [Troubleshooting Guide](./TROUBLESHOOTING.md) first
2. Reproduce the issue with minimal steps
3. Check [GitHub Issues](https://github.com/dcardenasl/ci4-admin-starter/issues) for duplicates
4. Open a new issue with:
   - Description of the problem
   - Steps to reproduce
   - Expected vs actual behavior
   - PHP/Composer/npm versions
   - Error logs or screenshots

### How do I request a feature?

Open a [GitHub Discussion](https://github.com/dcardenasl/ci4-admin-starter/discussions) or [Issue](https://github.com/dcardenasl/ci4-admin-starter/issues) with:
- Clear description of the feature
- Use cases and benefits
- Proposed implementation (if applicable)
- Any relevant examples or references

---

**Last Updated:** 2026-04-15

**Still have questions?** Reach out via GitHub Issues or check the [Documentation Hub](./INDEX.md).
