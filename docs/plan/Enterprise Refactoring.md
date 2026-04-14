# Plan: Enterprise Refactoring — CI4 Admin Starter como Template

## Contexto

Este proyecto está completamente implementado (9 dominios, 117 tests pasando) pero tiene estructura plana que no escala para equipos. Necesita tres cambios fundamentales antes de poder publicarse como template empresarial:

1. **Seguridad:** CDN de Tailwind Play activo en producción (bloqueante absoluto)
2. **Deuda técnica:** Métodos duplicados, abstracciones incompletas, magic strings de sesión
3. **Arquitectura:** Reorganización por dominios usando el sistema de Módulos nativo de CI4

El objetivo es que cada equipo que tome un dominio (ej. "equipo de Archivos") vea un directorio `app/Modules/Files/` con todo lo suyo y nada más.

---

## Estructura Final Propuesta

```
app/
├── Config/                          # Configuración global (sin rutas de dominio)
│   ├── Autoload.php                 ← MODIFICAR: registrar 9 namespaces de módulos
│   ├── Modules.php                  ← MODIFICAR: activar auto-discovery
│   ├── Routes.php                   ← MODIFICAR: solo redirect raíz
│   └── Services.php                 ← MODIFICAR: fix tipo retorno + ProfileApiService
├── Controllers/
│   ├── BaseController.php           # Sin cambios
│   └── BaseWebController.php        ← MODIFICAR: DRY fix + SessionKeys
├── Filters/                         # AuthFilter, AdminFilter, LocaleFilter (kernel)
├── Helpers/                         # badge, form, ui (globales, no van a módulos)
├── Language/en,es/                  # App, Validation, TableA11y, TableColumns, Errors
├── Libraries/                       # ApiClient + ApiClientInterface
├── Requests/                        # BaseFormRequest + FormRequestInterface
├── Services/                        # BaseApiService, ResourceApiService, CatalogApiService, HealthApiService
├── Support/
│   ├── CatalogOptions.php
│   ├── FileSizeLimits.php
│   └── SessionKeys.php              ← NUEVO: constantes de sesión centralizadas
└── Modules/
    ├── Auth/
    │   ├── Config/Routes.php
    │   ├── Controllers/AuthController.php
    │   ├── Language/en/Auth.php, es/Auth.php
    │   ├── Requests/ (Login, Register, ForgotPassword, ResetPassword, GoogleLogin)
    │   └── Services/AuthApiService.php
    ├── Dashboard/
    │   ├── Config/Routes.php
    │   ├── Controllers/DashboardController.php
    │   └── Language/en/Dashboard.php, es/Dashboard.php
    ├── Profile/
    │   ├── Config/Routes.php
    │   ├── Controllers/ProfileController.php
    │   ├── Language/en/Profile.php, es/Profile.php
    │   ├── Requests/ProfileUpdateRequest.php
    │   └── Services/ProfileApiService.php  ← NUEVO
    ├── Files/
    │   ├── Config/Routes.php
    │   ├── Controllers/FileController.php
    │   ├── Language/en/Files.php, es/Files.php
    │   ├── Requests/FileUploadRequest.php
    │   └── Services/FileApiService.php
    ├── Users/
    │   ├── Config/Routes.php
    │   ├── Controllers/UserController.php
    │   ├── Language/en/Users.php, es/Users.php
    │   ├── Requests/ (UserStore, UserUpdate)
    │   └── Services/UserApiService.php
    ├── Audit/
    │   ├── Config/Routes.php
    │   ├── Controllers/AuditController.php
    │   ├── Language/en/Audit.php, es/Audit.php
    │   └── Services/AuditApiService.php
    ├── ApiKeys/
    │   ├── Config/Routes.php
    │   ├── Controllers/ApiKeyController.php
    │   ├── Language/en/ApiKeys.php, es/ApiKeys.php
    │   ├── Requests/ (ApiKeyStore, ApiKeyUpdate)
    │   └── Services/ApiKeyApiService.php
    ├── Metrics/
    │   ├── Config/Routes.php
    │   ├── Controllers/MetricsController.php
    │   ├── Language/en/Metrics.php, es/Metrics.php
    │   └── Services/MetricsApiService.php
    └── Language/
        ├── Config/Routes.php           # POST /language/set
        └── Controllers/LanguageController.php
```

**Views se quedan en `app/Views/` en Phase 3** (bajo riesgo). Moverlas a módulos es Phase 4 opcional.

**Servicios que quedan en el kernel** (usados por múltiples módulos): `CatalogApiService`, `HealthApiService`, `BaseApiService`, `ResourceApiService`.

---

## Phase 1 — Seguridad (Bloqueantes de Template)

**Riesgo: Bajo-Medio | Tests afectados: 1 archivo**

### S1: Reemplazar Tailwind Play CDN con CSS compilado
- **Archivo:** `app/Views/layouts/partials/head.php`
- Eliminar `<script src="https://cdn.tailwindcss.com"></script>`
- Descomentar `<link rel="stylesheet" href="/assets/css/app.css">`
- Eliminar el bloque `tailwind.config = {...}` inline (solo lo necesita el CDN Play)
- Mantener el bloque `<style>` con variables CSS y `[x-cloak]`
- Verificar: `npm run build` antes de desplegar para asegurar CSS compilado actualizado

### S2: Verificar SRI hashes de Alpine.js y Lucide
- Alpine 3.14.9 y Lucide ya tienen atributos `integrity` en `head.php` — confirmar que los hashes son correctos
- El CDN de Tailwind (el único sin SRI) se elimina en S1

### S3: Convertir GET /language/set a POST + CSRF
- **`app/Config/Routes.php`:** cambiar `get('/language/set', ...)` a `post('/language/set', ..., ['as' => 'language.set'])`
- **`app/Controllers/LanguageController.php`:** cambiar `getGet('locale')` a `getPost('locale')`
- **Views con language switcher:** convertir `<a href="...">` a `<form method="POST">` con `<?= csrf_field() ?>` e input hidden `locale`
- **`tests/feature/LanguageFlowTest.php`:** cambiar `$this->get('/language/set?locale=es')` a `$this->post('/language/set', ['locale' => 'es'])`

### S4: Documentar drivers de sesión (sin código, solo docs)
- Agregar a `.env.example` comentario sobre Redis/database para scaling horizontal
- Mover `predis/predis` de `require-dev` a `require` en `composer.json` cuando se adopte Redis

---

## Phase 2 — Deuda Técnica

**Riesgo: Bajo | Tests afectados: 2 archivos**

### Q1: Eliminar métodos duplicados de BaseWebController
- **`app/Controllers/BaseWebController.php`:** eliminar los métodos `resolveDateRange()` e `isValidDate()` (ya existen idénticos en el `TableResponseTrait` que usa la clase)
- El trait ya provee ambos métodos; la clase los shadowa innecesariamente

### Q2: Crear ProfileApiService
- **Nuevo:** `app/Services/ProfileApiService.php` — wrappea `me()`, `update(userId, payload)`, `forgotPassword(email, url)`, `resendVerification(payload)`
- **`app/Config/Services.php`:** agregar factory `profileApiService()` → `new ProfileApiService(static::apiClient())`
- **`app/Controllers/ProfileController.php`:** reemplazar las llamadas directas a `$this->authService` y `$this->userService` por `$this->profileService`
- **Tests:** `tests/feature/ProfileFlowTest.php` — actualizar mocks de `authApiService`/`userApiService` a `profileApiService`

### Q3: Mover traducción hardcodeada de localizeApiMessage() a Language files
- **`app/Controllers/BaseWebController.php`:** cambiar la clave del mapa de strings ingleses crudos (`'This email is already registered'`) a claves de API (`'email_already_registered'`)
- Agregar TODO documentando que la solución definitiva es que la API retorne claves de traducción, no prose en inglés

### Q4: Fix FileApiService::upload() para usar multipart
- **`app/Services/FileApiService.php`:** reemplazar el método que encoda en Base64 + POST JSON por `$this->apiClient->upload('/files/upload', [...], $fields)` — el método multipart ya existe en ApiClient
- **`tests/unit/Services/FileApiServiceTest.php`:** cambiar expectativa de `post()` con base64 a `upload()` con estructura multipart

### Q5: Aplicar resolveCatalogs() consistentemente
- **`app/Controllers/AuditController.php`, `FileController.php`, `MetricsController.php`:** reemplazar el patrón inline de 3 líneas con `$catalogs = $this->resolveCatalogs($this->catalogService)` (ya funciona en UserController y ApiKeyController)

### Q6: Fix tipo de retorno de Services::apiClient()
- **`app/Config/Services.php`:** cambiar `public static function apiClient(): ApiClient` a `ApiClientInterface`
- Solo cambia el tipo declarado; implementación sin cambios

### Q7: Centralizar magic strings de sesión
- **Nuevo:** `app/Support/SessionKeys.php` con constantes `ACCESS_TOKEN`, `REFRESH_TOKEN`, `EXPIRES_AT`, `USER`, `LOCALE`
- **Usar en:** `app/Libraries/ApiClient.php`, `app/Controllers/AuthController.php`, `app/Filters/AuthFilter.php`

### Q8: Eliminar código muerto
- **Eliminar:** `app/Controllers/Home.php`
- **Eliminar:** `app/Views/welcome_message.php`

### T1: Fix phpunit.xml.dist
- Cambiar `failOnWarning="true"` a `failOnWarning="false"` para que `composer test` no falle sin Xdebug

### T2: Agregar quality gate en composer.json
- Agregar script `"ci": ["@test", "phpstan analyse", "php-cs-fixer fix --dry-run"]`

---

## Phase 3 — Reorganización por Dominios (CI4 Módulos)

**Riesgo: Medio | Tests afectados: ~18 archivos (solo imports `use`)**

### Paso 1: Registrar namespaces de módulos
- **`app/Config/Autoload.php`:** agregar en `$psr4`:
  ```php
  'App\Modules\Auth'      => APPPATH . 'Modules/Auth',
  'App\Modules\Dashboard' => APPPATH . 'Modules/Dashboard',
  'App\Modules\Profile'   => APPPATH . 'Modules/Profile',
  'App\Modules\Files'     => APPPATH . 'Modules/Files',
  'App\Modules\Users'     => APPPATH . 'Modules/Users',
  'App\Modules\Audit'     => APPPATH . 'Modules/Audit',
  'App\Modules\ApiKeys'   => APPPATH . 'Modules/ApiKeys',
  'App\Modules\Metrics'   => APPPATH . 'Modules/Metrics',
  'App\Modules\Language'  => APPPATH . 'Modules/Language',
  ```

### Paso 2: Activar auto-discovery de rutas de módulos
- **`app/Config/Modules.php`:** `$enabled = true`, `$discoverInComposer = false`
- CI4 buscará `Config/Routes.php` en cada namespace registrado automáticamente

### Paso 3: Crear Config/Routes.php por módulo
Cada módulo tiene su archivo de rutas con las mismas rutas actuales pero apuntando al nuevo FQCN del controller:
- Auth: rutas públicas (login, register, etc.)
- Dashboard: `filter: 'auth'`, named route `'dashboard'`
- Profile: `filter: 'auth'`, named routes
- Files: `filter: 'auth'`
- Users/Audit/ApiKeys/Metrics: `filter: ['auth', 'admin']`
- Language: `post('/language/set', ...)` (del fix S3)

**`app/Config/Routes.php`:** vaciar excepto el redirect raíz `/` → `/login`

### Paso 4: Migrar controladores (uno por uno, dominio por dominio)
Para cada controller:
1. Copiar a `app/Modules/{Domain}/Controllers/{Name}Controller.php`
2. Cambiar namespace: `App\Controllers` → `App\Modules\{Domain}\Controllers`
3. Mantener `extends BaseWebController` (clase kernel)
4. Actualizar imports `use` de servicios/requests al nuevo namespace de módulo
5. Los `service('userApiService')` no cambian — usan alias de string
6. Eliminar el original de `app/Controllers/`

### Paso 5: Migrar servicios de dominio
Mover de `app/Services/` a `app/Modules/{Domain}/Services/`:
- `AuthApiService` → `App\Modules\Auth\Services\`
- `UserApiService` → `App\Modules\Users\Services\`
- `AuditApiService` → `App\Modules\Audit\Services\`
- `ApiKeyApiService` → `App\Modules\ApiKeys\Services\`
- `MetricsApiService` → `App\Modules\Metrics\Services\`
- `FileApiService` → `App\Modules\Files\Services\`
- `ProfileApiService` → `App\Modules\Profile\Services\`

**`app/Config/Services.php`:** actualizar solo los `use` imports con nuevos FQCNs. Los métodos factory y alias string no cambian.

### Paso 6: Migrar Requests de dominio
Mover a `app/Modules/{Domain}/Requests/`:
- Auth: Login, Register, ForgotPassword, ResetPassword, GoogleLogin
- Files: FileUpload
- Profile: ProfileUpdate
- Users: UserStore, UserUpdate
- ApiKeys: ApiKeyStore, ApiKeyUpdate

`BaseFormRequest` y `FormRequestInterface` se quedan en `app/Requests/`.

### Paso 7: Migrar Language files de dominio
Mover `app/Language/{en,es}/{Domain}.php` a `app/Modules/{Domain}/Language/{en,es}/`:
- Auth, Dashboard, Profile, Files, Users, Audit, ApiKeys, Metrics
- CI4 descubre archivos de Language por namespace automáticamente

**Se quedan en `app/Language/`:** App.php, Validation.php, TableA11y.php, TableColumns.php, Errors.php (usados por múltiples módulos y helpers del kernel)

### Paso 8: Actualizar tests
- En los ~18 tests que importan servicios por FQCN: cambiar `use App\Services\UserApiService` a `use App\Modules\Users\Services\UserApiService`
- Los `Services::injectMock('userApiService', $mock)` no cambian (alias string)
- Los `$this->get('/admin/users')` no cambian (URLs públicas no cambian)

---

## Phase 4 — Quality Gates

**Riesgo: Bajo | Post Phase 3**

### PHPStan
- Actualizar `paths` en `phpstan.neon` para incluir `app/Modules`
- Regenerar baseline: `vendor/bin/phpstan analyse --generate-baseline`
- Subir de `level: 7` a `level: 8`

### phpunit.xml.dist
- Agregar `failOnDeprecation="false"` para deprecations de CI4 en tests
- Evaluar `<minimum><line>60</line></minimum>` como floor de cobertura

### Views (opcional, bajo riesgo)
- Mover `app/Views/{domain}/` a `app/Modules/{Domain}/Views/` para completar el encapsulamiento
- CI4 busca views en los paths de módulos cuando el controller está en ese namespace

---

## Decisiones de Diseño Clave

| Decisión | Por qué |
|---|---|
| Views se quedan en Phase 3 | Bajo riesgo sin cambios de comportamiento; separar del riesgo de namespace migration |
| Dashboard mantiene 4 servicios cross-domain | Es un BFF (Backend for Frontend) legítimo; la dependencia cross-domain se hace explícita con imports |
| Profile obtiene su propio servicio | El controller mezclaba llamadas de Auth y Users sin abstracción; la separación es correcta |
| CatalogApiService y HealthApiService en kernel | Usados por múltiples módulos; moverlos a un módulo crearía dependencia cross-módulo |
| Language files globales se quedan en app/ | App.php/Validation.php/TableColumns.php los usan helpers y layouts que no son de ningún dominio |
| Alias string de services no cambia | Preserva compatibilidad de tests y facilita mocking |

---

## Verificación End-to-End

```bash
# Después de cada fase:
vendor/bin/phpunit                          # 117+ tests deben pasar
vendor/bin/php-cs-fixer fix --dry-run       # 0 cambios de estilo
vendor/bin/phpstan analyse                  # 0 errores

# Phase 1 específico:
# Abrir la app en browser y verificar que CSS carga correctamente
# Verificar que language switcher funciona (POST)

# Phase 3 específico:
php spark routes                            # Confirmar que todas las rutas están registradas
php spark serve --port 8082                 # Navegar manualmente por todos los módulos
```

---

## Archivos Críticos a Modificar

| Archivo | Phase | Cambio |
|---|---|---|
| `app/Views/layouts/partials/head.php` | 1 | CDN → CSS compilado |
| `app/Config/Routes.php` | 1, 3 | GET→POST language; vaciar en Phase 3 |
| `app/Controllers/LanguageController.php` | 1 | getGet→getPost |
| `app/Controllers/BaseWebController.php` | 2 | Eliminar métodos duplicados |
| `app/Services/ProfileApiService.php` | 2 | NUEVO |
| `app/Services/FileApiService.php` | 2 | Base64→multipart |
| `app/Support/SessionKeys.php` | 2 | NUEVO |
| `app/Config/Services.php` | 2, 3 | Fix tipo retorno + imports de módulos |
| `app/Config/Autoload.php` | 3 | +9 namespaces PSR-4 |
| `app/Config/Modules.php` | 3 | enabled=true |
| `phpunit.xml.dist` | 2 | failOnWarning=false |
| `phpstan.neon` | 4 | +app/Modules path, level 8 |
