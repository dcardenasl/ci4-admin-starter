# Guía de Capa de Validación (`app/Modules/*/Requests`)

## Objetivo

Estandarizar validaciones web en una capa dedicada para:

- Mantener controladores delgados.
- Reutilizar reglas por caso de uso.
- Normalizar payloads antes de llamar servicios API.
- Evitar duplicar reglas de negocio del backend.

## Principios

- Frontend valida sintaxis/UI: `required`, formato, longitud, enums simples.
- Backend valida negocio: unicidad, estado, permisos, invariantes de dominio.
- Mensajes visibles al usuario deben usar `lang('...')`.
- Errores de formulario se exponen como `fieldErrors` en sesión.

## Arquitectura

Cada módulo tiene sus propias clases de validación de request. Piezas principales:

- `app/Modules/{ModuleName}/Requests/` — Todas las clases FormRequest para este módulo
- `app/Modules/{ModuleName}/Controllers/` — Controladores que utilizan estos requests
- `app/Config/Services.php` (`formRequest(...)`) — Factory de servicio para resolver requests
- `app/Controllers/BaseWebController.php` (`validateRequest(...)`) — Método helper para validación

Flujo estándar:

1. Resolver request class con `service('formRequest', RequestClass::class, false)`.
2. Validar request.
3. Obtener payload normalizado con `payload()`.
4. Consumir API service.
5. Resolver errores backend con `failApi()`.

## Ejemplo Mínimo en Controlador

```php
// Archivo: app/Modules/Auth/Controllers/AuthController.php

/** @var \App\Modules\Auth\Requests\LoginRequest $request */
$request = service('formRequest', \App\Modules\Auth\Requests\LoginRequest::class, false);
$invalid = $this->validateRequest($request);
if ($invalid !== null) {
    return $invalid;
}

$response = $this->safeApiCall(fn() => $this->authService->login($request->payload()));
```

## Módulos Actuales

### Módulo Auth

Ubicación: `app/Modules/Auth/Requests/`

- `LoginRequest.php`
- `RegisterRequest.php`
- `ForgotPasswordRequest.php`
- `ResetPasswordRequest.php`
- `ResetPasswordConfirmRequest.php`

### Módulo Users

Ubicación: `app/Modules/Users/Requests/`

- `UserStoreRequest.php`
- `UserUpdateRequest.php`

### Módulo API Keys

Ubicación: `app/Modules/ApiKeys/Requests/`

- `ApiKeyStoreRequest.php`
- `ApiKeyUpdateRequest.php`

### Módulo Profile

Ubicación: `app/Modules/Profile/Requests/`

- `ProfileUpdateRequest.php`
- `ChangePasswordRequest.php`

### Módulo Files

Ubicación: `app/Modules/Files/Requests/`

- `FileUploadRequest.php`

## Cómo Agregar un Nuevo FormRequest

1. Crear clase en `app/Modules/{ModuleName}/Requests/{CaseName}Request.php` extendiendo `BaseFormRequest`.
   - Ejemplo: `app/Modules/Users/Requests/UserStoreRequest.php`
2. Definir las reglas de validación en el método `rules()`.
3. Definir el payload normalizado en el método `payload()` (si es necesario).
4. Agregar strings de idioma en `app/Modules/{ModuleName}/Language/{en,es}/{ModuleName}.php` para mensajes de error.
5. Usar request en controller vía `service('formRequest', \App\Modules\{ModuleName}\Requests\{RequestName}::class, false)`.
6. Evitar reglas inline en controller.

## Testing Recomendado

Unit tests: Verificar normalización de `payload()` y reglas.
Feature tests: Validar redirects y `fieldErrors`.
