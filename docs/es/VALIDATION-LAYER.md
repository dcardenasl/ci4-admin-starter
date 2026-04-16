# Guía de Capa de Validación (`app/Requests`)

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

Piezas principales:

- `app/Requests/FormRequestInterface.php`
- `app/Requests/BaseFormRequest.php`
- `app/Config/Services.php` (`formRequest(...)`)
- `app/Controllers/BaseWebController.php` (`validateRequest(...)`)

Flujo estándar:

1. Resolver request class con `service('formRequest', RequestClass::class, false)`.
2. Validar request.
3. Obtener payload normalizado con `payload()`.
4. Consumir API service.
5. Resolver errores backend con `failApi()`.

## Ejemplo Mínimo en Controlador

```php
/** @var \App\Requests\Auth\LoginRequest $request */
$request = service('formRequest', \App\Requests\Auth\LoginRequest::class, false);
$invalid = $this->validateRequest($request);
if ($invalid !== null) {
    return $invalid;
}

$response = $this->safeApiCall(fn() => $this->authService->login($request->payload()));
```

## Módulos Actuales

### Auth
- `app/Requests/Auth/LoginRequest.php`
- `app/Requests/Auth/RegisterRequest.php`
- `app/Requests/Auth/ForgotPasswordRequest.php`
- `app/Requests/Auth/ResetPasswordRequest.php`

### Users
- `app/Requests/User/UserStoreRequest.php`
- `app/Requests/User/UserUpdateRequest.php`

### API Keys
- `app/Requests/ApiKey/ApiKeyStoreRequest.php`
- `app/Requests/ApiKey/ApiKeyUpdateRequest.php`

### Profile
- `app/Requests/Profile/ProfileUpdateRequest.php`

### Files
- `app/Requests/File/FileUploadRequest.php`

## Cómo Agregar un Nuevo FormRequest

1. Crear clase en `app/Requests/<Dominio>/<Caso>Request.php` extendiendo `BaseFormRequest`.
2. Definir `fields()`.
3. Definir `rules()`.
4. Sobrescribir `payload()` si se requiere normalización.
5. Usar request en controller vía `service('formRequest', ..., false)`.
6. Evitar reglas inline en controller.

## Testing Recomendado

Unit tests: Verificar normalización de `payload()` y reglas.
Feature tests: Validar redirects y `fieldErrors`.
