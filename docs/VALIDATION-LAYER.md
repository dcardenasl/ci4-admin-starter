# Validation Layer Guide (`app/Modules/*/Requests`)

## Objective

Standardize web validations in a dedicated layer to:

- Keep controllers thin.
- Reuse rules by use case.
- Normalize payloads before calling API services.
- Avoid duplicating backend business logic rules.

## Principles

- Frontend validates syntax/UI: `required`, format, length, simple enums.
- Backend validates business logic: uniqueness, state, permissions, domain invariants.
- User-facing messages must use `lang('...')`.
- Form errors are exposed as `fieldErrors` in the session, derived from the canonical API `errors` map.

## Architecture

Each module has its own dedicated request validation classes. Main pieces:

- `app/Modules/{ModuleName}/Requests/` — All FormRequest classes for this module
- `app/Modules/{ModuleName}/Controllers/` — Controllers that use these requests
- `app/Config/Services.php` (`formRequest(...)`) — Service factory for resolving requests
- `app/Controllers/BaseWebController.php` (`validateRequest(...)`) — Helper method for validation

Standard flow:

1. Resolve request class with `service('formRequest', RequestClass::class, false)`.
2. Validate request.
3. Get normalized payload with `payload()`.
4. Consume API service.
5. Handle backend errors with `failApi()`.

## Minimal Example in Controller

```php
// File: app/Modules/Auth/Controllers/AuthController.php

/** @var \App\Modules\Auth\Requests\LoginRequest $request */
$request = service('formRequest', \App\Modules\Auth\Requests\LoginRequest::class, false);
$invalid = $this->validateRequest($request);
if ($invalid !== null) {
    return $invalid;
}

$response = $this->safeApiCall(fn() => $this->authService->login($request->payload()));
```

## Current Modules

### Auth Module

Location: `app/Modules/Auth/Requests/`

Requests:

- `LoginRequest.php`
- `RegisterRequest.php`
- `ForgotPasswordRequest.php`
- `ResetPasswordRequest.php`
- `ResetPasswordConfirmRequest.php`

Key rules:

- `email` with `valid_email`.
- Password minimum based on flow (`login` vs `register/reset`).
- Password confirmation with `matches[password]`.

### Users Module

Location: `app/Modules/Users/Requests/`

Requests:

- `UserStoreRequest.php`
- `UserUpdateRequest.php`

Key rules:

- `first_name`, `last_name`, `email`, `role`.
- `role` limited to `user,admin`.

Key normalization:

- On update, `email` is omitted from payload if unchanged (`original_email`).

### API Keys Module

Location: `app/Modules/ApiKeys/Requests/`

Requests:

- `ApiKeyStoreRequest.php`
- `ApiKeyUpdateRequest.php`

Key rules:

- Create: `name` required.
- Update: fields `permit_empty`.
- Numeric limits with `is_natural_no_zero`.

Key normalization:

- `name` with `trim`.
- `is_active` converted to boolean.
- Rate limits converted to `int`.

### Profile Module

Location: `app/Modules/Profile/Requests/`

Requests:

- `ProfileUpdateRequest.php`
- `ChangePasswordRequest.php`

Key rules:

- `first_name` and `last_name` required with min/max length.
- Password validation with confirmation.

### Files Module

Location: `app/Modules/Files/Requests/`

Requests:

- `FileUploadRequest.php`

Key rules:

- `uploaded[file]` + `max_size[file,X]` (where `X` is calculated from the effective limit).
- Effective limit: `min(FILE_MAX_SIZE, upload_max_filesize, post_max_size)`.
- Support for AJAX validation with JSON response (`ok: false` plus session `fieldErrors` derived from API `errors`).

Key normalization:

- `payload()` returns `visibility` with default `private`.
- Dynamic error messages that include the maximum allowed file size in MB.

## How to Add a New FormRequest

1. Create a class in `app/Modules/{ModuleName}/Requests/{CaseName}Request.php` extending `BaseFormRequest`.
   - Example: `app/Modules/Users/Requests/UserStoreRequest.php`
2. Define the validation rules in `rules()` method.
3. Define the normalized payload in `payload()` method (if needed).
4. Add language strings to `app/Modules/{ModuleName}/Language/{en,es}/{ModuleName}.php` for error messages.
5. Use request in controller via `service('formRequest', \App\Modules\{ModuleName}\Requests\{RequestName}::class, false)`.
6. Avoid inline rules in controller.

## Recommended Testing

Unit tests:

- Verify `payload()` normalization.
- Verify rules and relevant conditional scenarios.
- Test edge cases and boundary conditions.

Feature tests:

- Validate redirects and derived `fieldErrors` in session.
- Validate that payload sent to API service preserves expected contract.
- Test form submission with valid and invalid data.

Current test references:

- `tests/unit/Requests/` — Unit tests for FormRequest classes
- `tests/feature/` — Feature tests for complete workflows including form submission

## PR Review Checklist

- No new inline rules in controllers.
- Request class exists for each new/modified form.
- Contract with backend is preserved (fields, types, semantic HTTP).
- User-facing messages use `lang()`.
- Tests are added/updated.
