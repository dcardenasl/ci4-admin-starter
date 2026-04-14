# Plan: Eliminación de Deuda Técnica — CI4 Admin Starter

## Contexto

El proyecto tiene una arquitectura bien pensada pero ha acumulado deuda técnica que dificultará la incorporación de nuevos miembros al equipo y el mantenimiento a largo plazo. Las principales áreas de deuda son: clases base que crecieron demasiado, duplicación de lógica entre controllers, falta de tests en flujos críticos, helpers sin cohesión, y tooling de calidad que no está completamente configurado.

Este plan organiza las mejoras por impacto real en mantenibilidad de equipo, de mayor a menor prioridad.

---

## BLOQUE 1 — Alta Prioridad: Eliminar duplicación y reducir acoplamiento

### 1.1 Extraer lógica de tabla de `BaseWebController` a un Trait

**Problema:** `BaseWebController` tiene 538 líneas. Los métodos `resolveTableState()`, `buildTableApiParams()`, `resolveTablePagination()`, `tableDataResponse()` son ~180 líneas de lógica de tabla que no deberían mezclarse con helpers de render y redirect.

**Solución:** Crear `app/Traits/TableResponseTrait.php` con esos 4 métodos + `passthroughApiJsonResponse()`. `BaseWebController` usa el trait.

**Archivos afectados:**
- Crear: `app/Traits/TableResponseTrait.php`
- Modificar: `app/Controllers/BaseWebController.php` (añadir `use TableResponseTrait;`, eliminar ~185 líneas)

---

### 1.2 Mover `resolveCatalogs()` a `BaseWebController`

**Problema:** El método privado `resolveCatalogs()` es idéntico en `UserController` y `ApiKeyController`. `AuditController` y `FileController` repiten la misma lógica inline.

**Solución:** Mover como método protegido a `BaseWebController` recibiendo el service como parámetro.

```php
protected function resolveCatalogs(mixed $service): array
{
    $response = $this->safeApiCall(fn() => $service->index());
    $data = $this->extractData($response);
    return is_array($data) ? $data : [];
}
```

**Archivos afectados:**
- Modificar: `app/Controllers/BaseWebController.php`
- Modificar: `app/Controllers/UserController.php` (línea 147 — eliminar método privado, llamar al heredado)
- Modificar: `app/Controllers/ApiKeyController.php` (línea 145 — igual)
- Modificar: `app/Controllers/AuditController.php` (reemplazar lógica inline)

---

### 1.3 Corregir `BaseWebController::$apiClient` para usar la interfaz

**Problema:** `BaseWebController` declara `protected ApiClient $apiClient` (clase concreta), mientras que todos los services usan correctamente `ApiClientInterface`. Esto impide mockear el cliente en tests de controllers.

**Solución:** Cambiar el type hint a `ApiClientInterface`.

```php
// Antes:
protected ApiClient $apiClient;

// Después:
protected ApiClientInterface $apiClient;
```

**Archivos afectados:**
- Modificar: `app/Controllers/BaseWebController.php` (cambiar import y type hint)

---

### 1.4 Sincronizar `has_admin_access()` con `AdminFilter`

**Problema:** `AdminFilter` y `has_admin_access()` en `ui_helper.php` mantienen la misma lista de roles admin por separado. Si se agrega un rol nuevo, hay que actualizar en dos lugares.

**Solución:** Definir en `app/Config/Auth.php` los roles admin como configuración centralizada:

```php
public array $adminRoles = ['admin', 'superadmin'];
```

Tanto `AdminFilter` como `has_admin_access()` leen de esa config.

**Archivos afectados:**
- Crear: `app/Config/Auth.php`
- Modificar: `app/Filters/AdminFilter.php`
- Modificar: `app/Helpers/ui_helper.php` (función `has_admin_access`)

---

## BLOQUE 2 — Media Prioridad: Tests faltantes en flujos críticos

### 2.1 Agregar tests de `AuthController` para login y registro normales

**Problema:** Solo hay tests de Google OAuth. Los flujos `attemptLogin` y `attemptRegister` (el camino más común) no tienen tests.

**Tests a crear** en `tests/feature/AuthFlowTest.php`:
- `testAttemptLoginSuccessRedirectsToDashboard`
- `testAttemptLoginFailureShowsError`
- `testAttemptLoginRedirectsIfAlreadyAuthenticated`
- `testAttemptRegisterSuccessRedirectsToVerify`
- `testAttemptRegisterValidationFailure`

---

### 2.2 Agregar tests de `UserController` para show/edit/approve/delete

**Problema:** Solo existe test de `store` y `update`. Los otros métodos no tienen cobertura.

**Tests a crear** en `tests/feature/UserCRUDTest.php`:
- `testShowReturnsUserDetail`
- `testShowRedirectsWhenUserNotFound`
- `testEditRedirectsWhenUserNotFound`
- `testApproveSuccess`
- `testApproveFailure`
- `testDeleteSuccess`
- `testDeleteFailure`

---

### 2.3 Agregar unit tests para services sin cobertura

**Services sin tests:**
- `UserApiService` — falta cobertura de `list`, `get`, `create`, `update`, `delete`
- `AuditApiService` — sin ningún test unitario
- `FileApiService` — especialmente `upload()` que tiene lógica de Base64 no trivial

**Tests a crear:**
- `tests/unit/Services/UserApiServiceTest.php`
- `tests/unit/Services/AuditApiServiceTest.php`
- `tests/unit/Services/FileApiServiceTest.php`

---

### 2.4 Agregar tests para `form_helper.php`

**Problema:** `get_field_error()` y `render_field_error()` no tienen ningún test.

**Tests a crear** en `tests/unit/Helpers/FormHelperTest.php`:
- `testGetFieldErrorReturnsNullWhenNoErrors`
- `testGetFieldErrorReturnsValueFromSession`
- `testRenderFieldErrorReturnsEmptyStringWhenNoError`
- `testRenderFieldErrorRendersHtmlWhenErrorPresent`

---

## BLOQUE 3 — Media Prioridad: Tooling y configuración

### 3.1 Activar umbrales de cobertura en `phpunit.xml.dist`

**Problema:** El comentario en `phpunit.xml.dist` dice que se necesita PHPUnit 12+ para `minPercentage`, pero PHPUnit 11 ya lo soporta.

**Solución:** Actualizar a PHPUnit 11 y activar el threshold:
```xml
<coverage>
    <report>
        <clover outputFile="tests/coverage.xml"/>
        <html outputPath="tests/coverage/"/>
    </report>
    <require>
        <directory suffix=".php" minPercentage="60">app</directory>
    </require>
</coverage>
```

**Archivos afectados:**
- Modificar: `phpunit.xml.dist`
- Modificar: `composer.json` (actualizar `phpunit/phpunit` de `^10.5` a `^11.0`)

---

### 3.2 Agregar named routes en `Routes.php`

**Problema:** Los controllers y vistas usan `site_url('admin/users')` como string literal. Si cambia un segmento de URL, hay que actualizar múltiples archivos.

**Solución:** Añadir `->as('nombre')` a las rutas principales y reemplazar `site_url('...')` por `route_to('...')` en controllers y vistas.

**Rutas prioritarias:**
```php
$routes->get('dashboard', 'DashboardController::index', ['as' => 'dashboard']);
$routes->get('admin/users', 'UserController::index', ['as' => 'admin.users']);
$routes->get('admin/audit', 'AuditController::index', ['as' => 'admin.audit']);
$routes->get('admin/api-keys', 'ApiKeyController::index', ['as' => 'admin.api_keys']);
$routes->get('admin/metrics', 'MetricsController::index', ['as' => 'admin.metrics']);
```

**Archivos afectados:**
- Modificar: `app/Config/Routes.php`
- Modificar: controllers y vistas que usen `site_url('admin/...')` hardcodeado

---

### 3.3 Subir nivel de PHPStan de 5 a 7

**Problema:** PHPStan nivel 5 de 9 deja pasar errores de tipos que nivel 7 detectaría.

**Solución:** Actualizar `phpstan.neon` y corregir los errores que surjan.

**Archivos afectados:**
- Modificar: `phpstan.neon` (cambiar `level: 5` a `level: 7`)
- Corregir errores de tipo que surjan en `app/`

---

### 3.4 Documentar `WEBAPP_BASE_URL` en el archivo `env`

**Problema:** `BaseWebController::clientBaseUrl()` lee `WEBAPP_BASE_URL` pero no está documentada en el archivo `env` de ejemplo.

**Solución:** Añadir entrada comentada en el `env`:
```dotenv
# Optional: Override the webapp base URL (used in email links, redirects)
# WEBAPP_BASE_URL = 'http://localhost:8082'
```

**Archivos afectados:**
- Modificar: `env`

---

## BLOQUE 4 — Baja Prioridad: Limpieza de código

### 4.1 Corregir alias triviales en services

- `FileApiService::getDownload()` — es alias de `get()`. Eliminar y actualizar usos en `FileController`.
- `MetricsApiService::get()` — tiene firma incompatible con `ResourceApiService::get()`. Renombrar a `summary()` (ya existe) y eliminar `get()`.

**Archivos afectados:**
- Modificar: `app/Services/FileApiService.php`
- Modificar: `app/Services/MetricsApiService.php`
- Modificar: `app/Controllers/FileController.php`

---

### 4.2 Limpiar `DashboardController`

- Eliminar la variable duplicada `recent_activity` / `recentActivity` (líneas 92-93) — mantener solo `recent_activity`
- Eliminar la doble llamada a `has_admin_access()` — calcular una vez en variable `$isAdmin`

**Archivos afectados:**
- Modificar: `app/Controllers/DashboardController.php`
- Modificar: `app/Views/dashboard/index.php` (si usa `recentActivity` con camelCase)

---

### 4.3 Agregar `declare(strict_types=1)` a archivos PHP de `app/`

**Problema:** Solo 2 archivos en `app/Support/` tienen strict types. Conversiones de tipo silenciosas en PHP 8.x pueden enmascarar bugs.

**Solución:** Agregar `declare(strict_types=1);` a todos los archivos en `app/Controllers/`, `app/Services/`, `app/Filters/`, `app/Libraries/`.

---

### 4.4 Dividir `ui_helper.php`

**Problema:** 454 líneas con 22 funciones de naturaleza muy diferente (badges, navegación, clases CSS, lógica de negocio).

**Solución:** Crear `app/Helpers/badge_helper.php` con las funciones de badges y localización:
- `status_badge()`, `role_badge()`, `audit_action_badge()`, `audit_result_badge()`, `audit_severity_badge()`
- `localized_status()`, `localized_role()`, `localized_audit_action()`, etc.

Mantener en `ui_helper.php`: `active_nav()`, `has_active_filters()`, `query_without_page()`, `has_admin_access()`, `format_date()`, `ui_icon()`, y clases CSS de tabla/filtros.

Actualizar `app/Config/Autoload.php` para cargar también `badge_helper`.

---

## Verificación

### Por bloque:

**Bloque 1:**
```bash
vendor/bin/phpunit tests/unit        # Tests unitarios no deben romper
vendor/bin/phpunit tests/feature     # Tests de feature deben pasar igual
vendor/bin/phpstan analyse           # Sin nuevos errores
```

**Bloque 2:**
```bash
vendor/bin/phpunit tests/feature/AuthFlowTest.php
vendor/bin/phpunit tests/feature/UserCRUDTest.php
vendor/bin/phpunit tests/unit/Services/
vendor/bin/phpunit tests/unit/Helpers/FormHelperTest.php
```

**Bloque 3:**
```bash
vendor/bin/phpunit --coverage-text=tests/coverage.txt  # Verificar threshold
vendor/bin/phpstan analyse --level=7
```

**Global:**
```bash
composer test          # Suite completa
composer quality       # PHPStan + CS Fixer
```

---

## Resumen de archivos nuevos/modificados

| Acción | Archivo | Bloque |
|--------|---------|--------|
| CREAR | `app/Traits/TableResponseTrait.php` | 1.1 |
| CREAR | `app/Config/Auth.php` | 1.4 |
| CREAR | `app/Helpers/badge_helper.php` | 4.4 |
| CREAR | `tests/feature/AuthFlowTest.php` | 2.1 |
| CREAR | `tests/feature/UserCRUDTest.php` | 2.2 |
| CREAR | `tests/unit/Services/UserApiServiceTest.php` | 2.3 |
| CREAR | `tests/unit/Services/AuditApiServiceTest.php` | 2.3 |
| CREAR | `tests/unit/Services/FileApiServiceTest.php` | 2.3 |
| CREAR | `tests/unit/Helpers/FormHelperTest.php` | 2.4 |
| MODIFICAR | `app/Controllers/BaseWebController.php` | 1.1, 1.2, 1.3 |
| MODIFICAR | `app/Controllers/UserController.php` | 1.2 |
| MODIFICAR | `app/Controllers/ApiKeyController.php` | 1.2 |
| MODIFICAR | `app/Controllers/AuditController.php` | 1.2 |
| MODIFICAR | `app/Controllers/DashboardController.php` | 4.2 |
| MODIFICAR | `app/Filters/AdminFilter.php` | 1.4 |
| MODIFICAR | `app/Helpers/ui_helper.php` | 1.4, 4.4 |
| MODIFICAR | `app/Services/FileApiService.php` | 4.1 |
| MODIFICAR | `app/Services/MetricsApiService.php` | 4.1 |
| MODIFICAR | `app/Config/Routes.php` | 3.2 |
| MODIFICAR | `app/Config/Autoload.php` | 4.4 |
| MODIFICAR | `phpunit.xml.dist` | 3.1 |
| MODIFICAR | `composer.json` | 3.1 |
| MODIFICAR: `phpstan.neon` | 3.3 |
| MODIFICAR | `env` | 3.4 |
