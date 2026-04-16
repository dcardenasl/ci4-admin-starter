# Services & Validation

This document explains how the **CI4 Admin Starter** communicates with the backend API and how we ensure data integrity before sending requests.

## 🔌 The Service Pattern

To keep controllers thin and focused on UI orchestration, all API communication is encapsulated in **Services** located in **`app/Modules/{ModuleName}/Services/`**.

Example locations:
- `app/Modules/Users/Services/UserApiService.php`
- `app/Modules/Files/Services/FileApiService.php`
- `app/Modules/Auth/Services/AuthApiService.php`

### Core Characteristics:
- **Interfaces:** Every service should have a corresponding Interface (e.g., `UserApiServiceInterface.php`). This allows for easier mocking during tests.
- **Base Class:** Most services extend `BaseApiService`, which provides the `apiClient`.
- **Registration:** Services are registered in `app/Config/Services.php` as shared instances (via `service('usersApi')`).

### Example Service Call in a Controller:
```php
// 1. Get the service
$userService = service('usersApi');

// 2. Perform the call (wrapped for safety)
$response = $this->safeApiCall(fn() => $userService->findById($id));

// 3. Extract data or handle failure
if (!$response['ok']) {
    return $this->failApi($response, 'User not found');
}
$user = $this->extractData($response);
```

---

## ✅ Validation Layer (`FormRequest`)

We use a dedicated validation layer located in **`app/Modules/{ModuleName}/Requests/`** to separate UI/Form validation from business logic.

Example locations:
- `app/Modules/Users/Requests/UserStoreRequest.php`
- `app/Modules/Files/Requests/FileUploadRequest.php`
- `app/Modules/Auth/Requests/LoginRequest.php`

### 1. `rules()`
Defines the CodeIgniter 4 validation rules. This should focus on **syntax and UI constraints** (e.g., `required`, `valid_email`, `max_length`).
- Avoid rules that require database access (e.g., `is_unique`). These belong in the Backend API.

### 2. `payload()`
This is the most critical method. It **normalizes** the form data into the exact structure and naming (`snake_case`) expected by the API.
- Converts types (e.g., string to int/bool).
- Trims whitespace.
- Removes optional fields that are empty.

### 3. Usage in Controller
```php
// Resolve the request (use false to get a fresh instance)
$request = service('formRequest', UserStoreRequest::class, false);

// Validate and redirect back with errors if it fails
$invalid = $this->validateRequest($request);
if ($invalid !== null) {
    return $invalid;
}

// Get the cleaned, normalized payload
$payload = $request->payload();

// Send to API
$response = $this->safeApiCall(fn() => $this->userService->create($payload));
```

---

## 🛠️ Error Handling Helpers

The `BaseWebController` provides several helpers to handle API responses gracefully:

### `safeApiCall(callable $callback)`
Wraps an API call in a `try/catch` block. If the connection fails or an exception occurs, it returns a synthetic "ok: false" response instead of crashing the app.

### `failApi(array $response, string $fallbackMessage)`
Handles a failed API response by:
1.  Extracting `fieldErrors` and redirecting back to the form with highlighted errors.
2.  If no field errors exist, it uses the API's `message` (or the `fallbackMessage`) to show a flash error.

### `extractData(array $response)` / `extractItems(array $response)`
Helpers to pull the actual data out of the standard API response envelope, correctly handling nested `data` keys in paginated results.

---

## 🔄 Data Synchronization

- **Locales:** The `ApiClient` automatically sends the `Accept-Language` header.
- **Errors:** API error keys (e.g., `email_already_registered`) can be mapped to local translations in `BaseWebController::localizeApiMessage()`.
- **Snake Case:** Always use `snake_case` for field names in your `FormRequest` rules and payloads to match the Backend contract.
