# CI4 Admin Starter Template

Template base en CodeIgniter 4 para levantar nuevos proyectos de **frontend administrativo** server-rendered.

Este repositorio **no implementa reglas de negocio ni acceso directo a base de datos**.
Su funcion es consumir un backend API y representar vistas, formularios y flujos de administracion.

## Proposito del template

Este proyecto existe para estandarizar nuevos frontends administrativos con la misma arquitectura, convenciones y contrato de integracion.

Arquitectura objetivo:

`Browser -> CI4 Admin Starter (este repo) -> Backend API`

## Backend oficial y responsabilidad de capas

Regla obligatoria para cualquier proyecto nuevo creado desde este template:

- El backend de datos y reglas de negocio vive en **`ci-api-tester`**.
- La estructura y contrato de endpoints deben mantenerse alineados con **`ci4-api-starter`**.
- Este repositorio es solo la capa web/admin (UI + orquestacion de requests + manejo de sesion JWT).

En otras palabras:

- `ci-api-tester` / `ci4-api-starter` = fuente de verdad de negocio y persistencia.
- `ci4-admin-starter` = cliente web administrativo, sin logica de dominio persistente.

## Compatibilidad obligatoria con `ci4-api-starter`

Todo proyecto derivado de este template debe conservar compatibilidad total con el contrato API:

- Prefix API: `/api/v1`.
- Autenticacion por `Bearer JWT`.
- Refresh token con endpoint de refresh.
- Soporte completo para respuestas JSON exitosas y de error de todos los endpoints.
- No modificar unilateralmente nombres de campos JSON, codigos HTTP ni envelopes de respuesta sin coordinar backend.

Documento de referencia: `docs/COMPATIBILIDAD-API.md`.
Incluye contrato explicito de `search`, `filter[...]`, `sort`, `limit`, `page/cursor` y estructura de respuesta para listados.

## Manejo JSON estandar en este template

El cliente HTTP (`app/Libraries/ApiClient.php`) normaliza cada respuesta en esta estructura:

```php
[
    'ok'          => bool,
    'status'      => int,
    'data'        => array,
    'raw'         => string,
    'messages'    => array,
    'fieldErrors' => array,
]
```

Reglas clave:

- `messages` se extrae desde `message`, `messages[]` o `errors.general`.
- `fieldErrors` se extrae desde `errors.<campo>`.
- En endpoints `data` para tablas/listados (`/files/data`, `/admin/users/data`, etc.), el frontend puede reenviar el JSON crudo del backend para mantener contrato intacto.

## Capa de validaciones (FormRequest)

La validacion web se centraliza en `app/Requests` para mantener controladores delgados y consistentes.

Objetivo de esta capa:

- Evitar reglas inline en controladores.
- Reutilizar reglas y normalizacion de payload por caso de uso.
- Mantener separacion de responsabilidades: validacion UI/sintaxis en frontend, reglas de negocio en backend.

Componentes:

- `app/Requests/FormRequestInterface.php`: contrato comun (`rules()`, `data()`, `payload()`, `validate()`, `errors()`).
- `app/Requests/BaseFormRequest.php`: implementacion base con integracion a `ValidationInterface`.
- `app/Config/Services.php`: `formRequest(string $class, bool $getShared = true)` para instanciar requests tipados.
- `app/Controllers/BaseWebController.php`: helper `validateRequest()` para respuesta de error uniforme.

Flujo estandar en controladores:

1. Resolver request class via `service('formRequest', <RequestClass>::class, false)`.
2. Validar con `validateRequest()` o `request->validate()`.
3. Construir payload final con `request->payload()`.
4. Delegar llamada HTTP en `app/Services/*ApiService.php`.
5. Resolver errores del backend con `failApi()` y errores de formulario con `fieldErrors`.

Ejemplo corto:

```php
/** @var \App\Requests\Auth\LoginRequest $request */
$request = service('formRequest', \App\Requests\Auth\LoginRequest::class, false);
$invalid = $this->validateRequest($request);
if ($invalid !== null) {
    return $invalid;
}

$response = $this->safeApiCall(fn() => $this->authService->login($request->payload()));
```

Convenciones importantes:

- `rules()` define solo validaciones sintacticas/UI (`required`, `valid_email`, `max_length`, `in_list`, etc.).
- `payload()` debe normalizar tipos y omitir campos vacios cuando aplique.
- Los mensajes de UI deben usar `lang('...')`.
- No duplicar validaciones de dominio que pertenecen al backend.

## Requisitos

- PHP `^8.1`
- Composer 2.x
- Extensiones PHP minimas:
  - `intl`
  - `mbstring`
- Recomendadas:
  - `curl`
  - `json`

## Instalacion

```bash
composer install
cp env .env
```

Configurar en `.env`:

```dotenv
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8082/'
apiClient.baseUrl = 'http://localhost:8080'
# Opcional: API key para rate limit elevado (600 req/min vs 60 req/min por IP)
# Crear una via /admin/api-keys o POST /api/v1/api-keys
# apiClient.appKey = apk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## Desarrollo

```bash
php spark serve --port 8082
```

Aplicacion disponible en `http://localhost:8082`.

## Pruebas

```bash
vendor/bin/phpunit
```

Cobertura (opcional):

```bash
vendor/bin/phpunit --colors --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/
```

## Estructura relevante

- `app/Controllers`: flujo web y coordinacion de llamadas al API.
- `app/Requests`: validacion de formularios y normalizacion de payload por caso de uso.
- `app/Services`: servicios por dominio para encapsular endpoints (extienden `BaseApiService`).
- `app/Libraries/ApiClient.php`: cliente HTTP con auth/refresh, header `X-App-Key` y normalizacion de respuestas JSON.
- `app/Libraries/ApiClientInterface.php`: contrato del cliente HTTP.
- `app/Filters`: `AuthFilter`, `AdminFilter`, `LocaleFilter`.
- `app/Helpers`: `ui_helper.php` (utilidades de vista), `form_helper.php` (errores de campo).
- `app/Language/en/`, `app/Language/es/`: archivos de idioma (i18n).
- `app/Views`: interfaz administrativa server-rendered.
- `app/Config/ApiClient.php`: configuracion del backend API (baseUrl, timeouts, apiPrefix, appKey).
- `app/Config/Services.php`: factory de servicios compartidos y constructor de `FormRequest`.
- `docs/plan/PLAN-CI4-CLIENT.md`: historial de implementacion y referencia de arquitectura.
- `docs/COMPATIBILIDAD-API.md`: lineamientos de compatibilidad backend/frontend.
- `docs/VALIDATION-LAYER.md`: guia de la capa de validaciones (`FormRequest`) y convenciones.

## Regla para nuevos proyectos basados en este template

Si creas un nuevo proyecto desde este repositorio:

1. Mantener el frontend desacoplado de DB y reglas de negocio.
2. Implementar funcionalidades consumiendo endpoints existentes del backend.
3. Conservar y validar compatibilidad JSON/HTTP con `ci4-api-starter`.
4. Mantener validaciones de formularios en `app/Requests` (no inline en controllers).
5. Evitar cambios que rompan contratos sin versionamiento coordinado.

## Seguridad y despliegue

- `DocumentRoot` debe apuntar a `public/`.
- Nunca commitear secretos (`.env`, tokens, credenciales).
- `writable/` es solo runtime (logs, cache, sesiones, uploads).

## Referencias

- CodeIgniter 4 User Guide: <https://codeigniter.com/user_guide/>
- CI4 API Starter: <https://github.com/dcardenasl/ci4-api-starter>
- Plan del cliente admin: `docs/plan/PLAN-CI4-CLIENT.md`
- Guia de validaciones: `docs/VALIDATION-LAYER.md`
