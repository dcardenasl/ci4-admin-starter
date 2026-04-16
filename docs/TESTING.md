# Testing & Quality

This document outlines our strategy for ensuring the stability and reliability of the **CI4 Admin Starter**.

## 🧪 Testing Strategy

Since this project is a frontend that consumes an API, our testing focuses on two levels:

1.  **Unit Tests:** Testing individual components (like `FormRequest` normalization or `ApiClient` logic) in isolation.
2.  **Feature Tests:** Testing full controller actions (e.g., submitting a login form) by mocking the API responses.

---

## 🛠️ Running Tests

Use the following commands from the project root:

```bash
# Run all tests
vendor/bin/phpunit

# Run unit tests only
vendor/bin/phpunit tests/unit/

# Run feature tests only
vendor/bin/phpunit tests/feature/

# Run with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-text
```

---

## 🎭 Mocking the API

To test controllers without a running backend, we mock the **Services** instead of the `ApiClient`.

### Example: Mocking a Login Flow
In `tests/feature/AuthFlowTest.php`, we create a mock of `AuthApiService` and inject it into the CodeIgniter Service container:

```php
public function testLoginSuccess()
{
    // 1. Create the mock
    $authService = $this->createMock(AuthApiService::class);
    
    // 2. Define the expected API response
    $authService->method('login')->willReturn([
        'ok'     => true,
        'status' => 200,
        'data'   => [
            'access_token'  => 'fake-token',
            'refresh_token' => 'fake-refresh',
            'user'          => ['role' => 'admin']
        ]
    ]);

    // 3. Inject the mock
    Services::injectMock('authApiService', $authService);

    // 4. Perform the request
    $result = $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'password'
    ]);

    // 5. Assert results
    $result->assertRedirectTo('/dashboard');
    $result->assertSessionHas('access_token');
}
```

---

## 📐 Testing `FormRequest`

Validation logic should be tested in `tests/unit/Requests/`. Focus on:
- Ensuring `rules()` correctly block invalid data.
- Ensuring `payload()` correctly transforms form input into the `snake_case` API contract.

---

## 💎 Quality Tools

We use several tools to maintain high code standards:

### 1. PHPStan (Static Analysis)
Checks for type errors and potential bugs without running the code.
```bash
vendor/bin/phpstan analyse
```

### 2. PHP-CS-Fixer (Coding Style)
Ensures the codebase follows PSR-12 and CI4 standards.
```bash
# Check for issues
vendor/bin/php-cs-fixer fix --dry-run --diff

# Automatically fix issues
vendor/bin/php-cs-fixer fix
```

### 3. ESLint (JavaScript Style)
Checks the JavaScript files in `public/assets/js/`.
```bash
npm run lint:js
```

---

## ✅ Checklist for New Tests

- [ ] Does the test cover the "Happy Path" (success)?
- [ ] Does the test cover common error scenarios (validation failure, API 401, API 500)?
- [ ] Are all mocks properly reset in `tearDown()`?
- [ ] If adding a new Service, did you add an Interface and register it in `Config/Services.php` to make it mockable?
