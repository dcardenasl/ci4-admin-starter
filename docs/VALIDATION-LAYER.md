# Validation Layer Guide (`app/Requests`)

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
- Form errors are exposed as `fieldErrors` in the session.

## Architecture

Main pieces:

- `app/Requests/FormRequestInterface.php`
- `app/Requests/BaseFormRequest.php`
- `app/Config/Services.php` (`formRequest(...)`)
- `app/Controllers/BaseWebController.php` (`validateRequest(...)`)

Standard flow:

1. Resolve request class with `service('formRequest', RequestClass::class, false)`.
2. Validate request.
3. Get normalized payload with `payload()`.
4. Consume API service.
5. Handle backend errors with `failApi()`.

## Minimal Example in Controller

```php
/** @var \App\Requests\Auth\LoginRequest $request */
$request = service('formRequest', \App\Requests\Auth\LoginRequest::class, false);
$invalid = $this->validateRequest($request);
if ($invalid !== null) {
    return $invalid;
}

$response = $this->safeApiCall(fn() => $this->authService->login($request->payload()));
```

## Current Modules

### Auth

Requests:

- `app/Requests/Auth/LoginRequest.php`
- `app/Requests/Auth/RegisterRequest.php`
- `app/Requests/Auth/ForgotPasswordRequest.php`
- `app/Requests/Auth/ResetPasswordRequest.php`

Key rules:

- `email` with `valid_email`.
- Password minimum based on flow (`login` vs `register/reset`).
- Password confirmation with `matches[password]`.

### Users

Requests:

- `app/Requests/User/UserStoreRequest.php`
- `app/Requests/User/UserUpdateRequest.php`

Key rules:

- `first_name`, `last_name`, `email`, `role`.
- `role` limited to `user,admin`.

Key normalization:

- On update, `email` is omitted from payload if unchanged (`original_email`).

### API Keys

Requests:

- `app/Requests/ApiKey/ApiKeyStoreRequest.php`
- `app/Requests/ApiKey/ApiKeyUpdateRequest.php`

Key rules:

- Create: `name` required.
- Update: fields `permit_empty`.
- Numeric limits with `is_natural_no_zero`.

Key normalization:

- `name` with `trim`.
- `is_active` converted to boolean.
- Rate limits converted to `int`.

### Profile

Request:

- `app/Requests/Profile/ProfileUpdateRequest.php`

Key rules:

- `first_name` and `last_name` required with min/max length.

### Files

Request:

- `app/Requests/File/FileUploadRequest.php`

Key rules:

- `uploaded[file]` + `max_size[file,X]` (where `X` is calculated from the effective limit).
- Effective limit: `min(FILE_MAX_SIZE, upload_max_filesize, post_max_size)`.
- Support for AJAX validation with JSON response (`ok: false, fieldErrors: [...]`).

Key normalization:

- `payload()` returns `visibility` with default `private`.
- Dynamic error messages that include the maximum allowed file size in MB.

## How to Add a New FormRequest

1. Create a class in `app/Requests/<Domain>/<Case>Request.php` extending `BaseFormRequest`.
2. Define `fields()`.
3. Define `rules()`.
4. Override `payload()` if normalization is required.
5. Use request in controller via `service('formRequest', ..., false)`.
6. Avoid inline rules in controller.

## Recommended Testing

Unit tests:

- Verify `payload()` normalization.
- Verify rules and relevant conditional scenarios.

Feature tests:

- Validate redirects and `fieldErrors`.
- Validate that payload sent to API service preserves expected contract.

Current references:

- `tests/unit/Requests/User/UserUpdateRequestTest.php`
- `tests/unit/Requests/ApiKey/ApiKeyUpdateRequestTest.php`

## PR Review Checklist

- No new inline rules in controllers.
- Request class exists for each new/modified form.
- Contract with backend is preserved (fields, types, semantic HTTP).
- User-facing messages use `lang()`.
- Tests are added/updated.
