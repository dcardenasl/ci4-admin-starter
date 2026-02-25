# Validation Layer Guide (`app/Requests`)

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

## Ejemplo mínimo en Controller

```php
/** @var \App\Requests\Auth\LoginRequest $request */
$request = service('formRequest', \App\Requests\Auth\LoginRequest::class, false);
$invalid = $this->validateRequest($request);
if ($invalid !== null) {
    return $invalid;
}

$response = $this->safeApiCall(fn() => $this->authService->login($request->payload()));
```

## Módulos actuales

### Auth

Requests:

- `app/Requests/Auth/LoginRequest.php`
- `app/Requests/Auth/RegisterRequest.php`
- `app/Requests/Auth/ForgotPasswordRequest.php`
- `app/Requests/Auth/ResetPasswordRequest.php`

Reglas clave:

- `email` con `valid_email`.
- Password mínimo según flujo (`login` vs `register/reset`).
- Confirmación de password con `matches[password]`.

### Users

Requests:

- `app/Requests/User/UserStoreRequest.php`
- `app/Requests/User/UserUpdateRequest.php`

Reglas clave:

- `first_name`, `last_name`, `email`, `role`.
- `role` limitado a `user,admin`.

Normalización clave:

- En update, `email` se omite del payload si no cambió (`original_email`).

### API Keys

Requests:

- `app/Requests/ApiKey/ApiKeyStoreRequest.php`
- `app/Requests/ApiKey/ApiKeyUpdateRequest.php`

Reglas clave:

- Create: `name` requerido.
- Update: campos `permit_empty`.
- Límites numéricos con `is_natural_no_zero`.

Normalización clave:

- `name` con `trim`.
- `is_active` convertido a boolean.
- Rate limits convertidos a `int`.

### Profile

Request:

- `app/Requests/Profile/ProfileUpdateRequest.php`

Reglas clave:

- `first_name` y `last_name` requeridos con longitud mínima/máxima.

### Files

Request:

- `app/Requests/File/FileUploadRequest.php`

Reglas clave:

- `uploaded[file]` + `max_size[file,X]` (donde `X` se calcula desde `config('Validation')->maxFileSizeBytes`).
- Soporte para validación AJAX con respuesta JSON (`ok: false, fieldErrors: [...]`).

Normalización clave:

- `payload()` devuelve `visibility` con default `private`.
- Mensajes de error dinámicos que incluyen el tamaño máximo permitido en MB.

## Cómo agregar un nuevo FormRequest

1. Crear clase en `app/Requests/<Dominio>/<Caso>Request.php` extendiendo `BaseFormRequest`.
2. Definir `fields()`.
3. Definir `rules()`.
4. Sobrescribir `payload()` si se requiere normalización.
5. Usar request en controller vía `service('formRequest', ..., false)`.
6. Evitar reglas inline en controller.

## Testing recomendado

Unit tests:

- Verificar normalización de `payload()`.
- Verificar reglas/escenarios condicionales relevantes.

Feature tests:

- Validar redirects y `fieldErrors`.
- Validar que payload enviado a API service preserve contrato esperado.

Referencias actuales:

- `tests/unit/Requests/User/UserUpdateRequestTest.php`
- `tests/unit/Requests/ApiKey/ApiKeyUpdateRequestTest.php`

## Checklist de revisión (PR)

- No hay reglas inline nuevas en controladores.
- Existe request class para cada formulario nuevo/modificado.
- Se preserva contrato con backend (campos, tipos, semántica HTTP).
- Mensajes user-facing usan `lang()`.
- Se añadieron/actualizaron tests.
