# Plan: CI4 Admin Starter - Frontend Web Application

## Context

`ci4-admin-starter` se usa como **template frontend administrativo** para levantar nuevos proyectos CI4.
La comunicacion con base de datos y reglas de negocio se realiza en el backend (`ci-api-tester`), manteniendo compatibilidad de contrato con `ci4-api-starter`.

Este documento conserva el roadmap y las decisiones de implementacion, pero debe leerse bajo esta separacion de responsabilidades:

- Backend (`ci-api-tester` / contrato `ci4-api-starter`): dominio, persistencia y endpoints.
- Frontend (`ci4-admin-starter`): UI, sesion JWT server-side, consumo y manejo robusto de respuestas JSON.

## Decisiones del Usuario

- **Diseno**: Limpio y personalizable. Sin gradientes. CSS variables para colores/fuentes/logo. Cambiar marca = editar 1 archivo.
- **Alcance inicial**: Core primero (Fases 1-5): infraestructura, auth, dashboard, perfil y archivos (~35 archivos)
- **Fases 6-9** (admin: users, audit, metrics) se implementan despues

## Arquitectura General

```
Browser → CI4 Frontend App (port 8082) → CI4 API (port 8080)
```

- **Server-side rendering** con views de CI4
- **Tailwind CSS** (CDN) + **Alpine.js** (CDN) para interactividad
- **Tokens JWT almacenados en session PHP** (nunca expuestos al browser)
- **Auto-refresh transparente** de tokens en el ApiClient

---

## Estructura del Proyecto

```
ci4-admin-starter/
├── app/
│   ├── Config/
│   │   ├── ApiClient.php              # Config: baseUrl, timeout, connectTimeout, apiPrefix, appName, appKey
│   │   ├── Services.php               # Factory de servicios compartidos (apiClient, *ApiService)
│   │   ├── Routes.php                 # Todas las rutas web
│   │   ├── Filters.php                # Registrar AuthFilter, AdminFilter, LocaleFilter
│   │   └── Autoload.php               # Autoload ui_helper + form_helper
│   │
│   ├── Controllers/
│   │   ├── BaseWebController.php      # Base: ApiClient, viewData, helpers, table utils
│   │   ├── AuthController.php         # Login, register, forgot/reset password, verify email
│   │   ├── DashboardController.php    # Dashboard con stats y health del API
│   │   ├── ProfileController.php      # Perfil (edicion admin), reset password por email, reenviar verificacion
│   │   ├── FileController.php         # Gestion de archivos (upload, list, download, delete)
│   │   ├── UserController.php         # CRUD usuarios + aprobar (admin)
│   │   ├── AuditController.php        # Logs de auditoria (admin)
│   │   ├── ApiKeyController.php       # Gestion de API keys (admin)
│   │   ├── MetricsController.php      # Dashboard metricas (admin)
│   │   └── LanguageController.php     # Cambio de idioma via session
│   │
│   ├── Filters/
│   │   ├── AuthFilter.php             # Verifica session JWT, redirige a /login
│   │   ├── AdminFilter.php            # Verifica role=admin, redirige a /dashboard si no
│   │   └── LocaleFilter.php           # Establece locale desde session en cada request
│   │
│   ├── Libraries/
│   │   ├── ApiClient.php              # HTTP client central con auto-refresh JWT y X-App-Key
│   │   └── ApiClientInterface.php     # Contrato de metodos del ApiClient
│   │
│   ├── Services/
│   │   ├── BaseApiService.php         # Clase base: inyecta ApiClientInterface
│   │   ├── AuthApiService.php         # Llamadas auth al API
│   │   ├── UserApiService.php         # Llamadas users al API
│   │   ├── FileApiService.php         # Llamadas files al API
│   │   ├── AuditApiService.php        # Llamadas audit al API
│   │   ├── ApiKeyApiService.php       # Llamadas api-keys al API (admin)
│   │   ├── MetricsApiService.php      # Llamadas metrics al API (con fallback)
│   │   └── HealthApiService.php       # Health check del API (up/degraded/down)
│   │
│   ├── Requests/
│   │   ├── FormRequestInterface.php   # Contrato de validacion web por caso de uso
│   │   ├── BaseFormRequest.php        # Integracion base con ValidationInterface
│   │   ├── Auth/                      # Login, register, forgot/reset
│   │   ├── User/                      # Store/update users
│   │   ├── ApiKey/                    # Store/update api-keys
│   │   ├── Profile/                   # Update profile
│   │   └── File/                      # Upload file
│   │
│   ├── Helpers/
│   │   ├── ui_helper.php              # active_nav, format_date, status_badge, table classes, ui_icon
│   │   └── form_helper.php            # get_field_error, has_field_error, render_field_error
│   │
│   ├── Language/
│   │   ├── en/                        # App, Auth, Dashboard, Files, Users, Audit, ApiKeys, Metrics, Profile, Validation
│   │   └── es/                        # Mismo set de archivos en español
│   │
│   └── Views/
│       ├── layouts/
│       │   ├── app.php                # Layout autenticado (sidebar + navbar)
│       │   ├── auth.php               # Layout publico (card centrado)
│       │   └── partials/
│       │       ├── head.php           # <head>: Tailwind CDN, Alpine CDN, Lucide CDN, theme
│       │       ├── sidebar.php        # Navegacion lateral colapsable
│       │       ├── navbar.php         # Barra superior con dropdown usuario y selector de idioma
│       │       ├── flash_messages.php # Toasts de notificacion
│       │       ├── confirm_modal.php  # Modal de confirmacion reutilizable
│       │       ├── pagination.php     # Paginacion por pagina
│       │       ├── remote_pagination.php  # Paginacion cursor/pagina para tablas server-driven
│       │       ├── filter_panel.php   # Panel de filtros colapsable reutilizable
│       │       └── table_toolbar.php  # Toolbar de tabla con busqueda y acciones
│       │
│       ├── auth/
│       │   ├── login.php              # Formulario login
│       │   ├── register.php           # Registro con indicador de fuerza de password
│       │   ├── forgot_password.php    # Solicitar reset de password
│       │   ├── reset_password.php     # Establecer nuevo password
│       │   └── verify_email.php       # Resultado de verificacion
│       │
│       ├── dashboard/
│       │   └── index.php              # Stats cards + archivos recientes + health del API
│       │
│       ├── profile/
│       │   └── index.php              # Perfil + reset password por email + reenviar verificacion
│       │
│       ├── files/
│       │   ├── index.php              # File manager: upload drag-and-drop + tabla server-driven
│       │   └── partials/              # filters.php, list_section.php
│       │
│       ├── users/
│       │   ├── index.php, show.php, create.php, edit.php
│       │   └── partials/              # filters.php, toolbar_actions.php
│       │
│       ├── audit/
│       │   ├── index.php, show.php
│       │   └── partials/              # filters.php
│       │
│       ├── api_keys/
│       │   ├── index.php, show.php, create.php, edit.php
│       │   └── partials/              # filters.php, toolbar_actions.php
│       │
│       ├── metrics/
│       │   ├── index.php
│       │   └── partials/              # filters.php
│       │
│       └── errors/
│           └── html/                  # error_404.php, error_400.php, production.php
│
├── public/
│   └── assets/js/
│       └── app.js                     # Alpine.js stores: toasts, confirm modal
│
└── .env                               # apiClient.baseUrl, apiClient.appKey (opcional), session config
```

**Total: ~80 archivos implementados**

---

## Componente Clave: ApiClient

El corazon de la app. Maneja TODA comunicacion con el API.

```
ApiClient
├── get(path, query)          # GET autenticado
├── post(path, data)          # POST autenticado (JSON)
├── put(path, data)           # PUT autenticado (JSON)
├── delete(path)              # DELETE autenticado
├── upload(path, files)       # POST multipart autenticado (cURL nativo)
├── publicPost(path, data)    # POST sin auth (login, register, etc.)
├── publicGet(path, query)    # GET sin auth
├── request(method, path, options, authenticated)  # Core: auto-refresh en 401
└── attemptTokenRefresh()     # POST /api/v1/auth/refresh → actualiza session
```

> El ApiClient tambien inyecta el header `X-App-Key` en cada request (publico y autenticado) cuando `apiClient.appKey` esta configurado en `.env`. Cuando esta ausente, el header no se envia y aplican los limites de rate por IP (60 req/min). Con una key valida, el limite sube a 600 req/min.

**Flujo auto-refresh:**
1. Request falla con 401
2. Intenta `POST /api/v1/auth/refresh` con refresh_token de session
3. Si exito: actualiza tokens en session, reintenta request original
4. Si falla: destruye session, retorna error (AuthFilter redirige a /login)

---

## Manejo de Tokens (Session)

```php
// Al hacer login exitoso, se guarda en session:
$session->set('access_token', $data['access_token']);
$session->set('refresh_token', $data['refresh_token']);
$session->set('token_expires_at', time() + $data['expires_in']);
$session->set('user', $data['user']); // {id, email, first_name, last_name, role}
```

- Tokens NUNCA se exponen al browser (solo en session PHP server-side)
- `AuthFilter` verifica existencia de `access_token` en session
- `AdminFilter` verifica `session('user.role') === 'admin'`

---

## Rutas

```php
// --- Utilitarias ---
GET  /language/set             → LanguageController::set

// --- Publicas ---
GET  /login                    → AuthController::login
POST /login                    → AuthController::attemptLogin
GET  /register                 → AuthController::register
POST /register                 → AuthController::attemptRegister
GET  /forgot-password          → AuthController::forgotPassword
POST /forgot-password          → AuthController::attemptForgotPassword
GET  /reset-password           → AuthController::resetPassword
POST /reset-password           → AuthController::attemptResetPassword
GET  /verify-email             → AuthController::verifyEmail
GET  /logout                   → AuthController::logout

// --- Autenticadas (filter: auth) ---
GET  /dashboard                → DashboardController::index
GET  /profile                  → ProfileController::index
POST /profile                  → ProfileController::update
POST /profile/request-password-reset → ProfileController::requestPasswordReset
POST /profile/resend-verification → ProfileController::resendVerification
GET  /files                    → FileController::index
GET  /files/data               → FileController::data       // JSON para tabla server-driven
POST /files/upload             → FileController::upload
GET  /files/{id}/download      → FileController::download (usa GET /api/v1/files/{id})
POST /files/{id}/delete        → FileController::delete

// --- Admin (filter: auth + admin) ---
GET  /admin/users              → UserController::index
GET  /admin/users/data         → UserController::data       // JSON para tabla server-driven
GET  /admin/users/create       → UserController::create
POST /admin/users              → UserController::store
GET  /admin/users/{id}         → UserController::show
GET  /admin/users/{id}/edit    → UserController::edit
POST /admin/users/{id}         → UserController::update
POST /admin/users/{id}/delete  → UserController::delete
POST /admin/users/{id}/approve → UserController::approve
GET  /admin/audit              → AuditController::index
GET  /admin/audit/data         → AuditController::data      // JSON para tabla server-driven
GET  /admin/audit/{id}         → AuditController::show
GET  /admin/audit/entity/{type}/{id} → AuditController::byEntity
GET  /admin/api-keys           → ApiKeyController::index
GET  /admin/api-keys/data      → ApiKeyController::data     // JSON para tabla server-driven
GET  /admin/api-keys/create    → ApiKeyController::create
POST /admin/api-keys           → ApiKeyController::store
GET  /admin/api-keys/{id}      → ApiKeyController::show
GET  /admin/api-keys/{id}/edit → ApiKeyController::edit
POST /admin/api-keys/{id}      → ApiKeyController::update
POST /admin/api-keys/{id}/delete → ApiKeyController::delete
GET  /admin/metrics            → MetricsController::index
```

---

## Estado de Implementacion

Todas las fases fueron completadas. El siguiente desglose conserva el historial de lo que se implemento en cada fase.

### ✅ Fase 1: Infraestructura core
- `app/Config/ApiClient.php` — configuracion del cliente HTTP (baseUrl, timeout, apiPrefix, appKey)
- `app/Libraries/ApiClient.php` — HTTP client con auto-refresh JWT y header `X-App-Key`
- `app/Libraries/ApiClientInterface.php` — contrato de metodos
- `app/Controllers/BaseWebController.php` — base con ApiClient, viewData, table utils, flash helpers
- `app/Filters/AuthFilter.php`, `AdminFilter.php`, `LocaleFilter.php` — filtros de acceso e idioma
- `app/Config/Filters.php` — registro de filtros
- `app/Config/Services.php` — factory de servicios compartidos
- `app/Helpers/ui_helper.php`, `form_helper.php` — helpers de vista y formulario
- `app/Config/Autoload.php` — carga global de helpers
- `.env` — `apiClient.baseUrl`, `apiClient.appKey` (opcional), session config

### ✅ Fase 2: Capa de servicios API
- `app/Services/BaseApiService.php` — clase base con inyeccion de ApiClientInterface
- `app/Services/AuthApiService.php`, `FileApiService.php`, `UserApiService.php`
- `app/Services/AuditApiService.php`, `ApiKeyApiService.php`
- `app/Services/MetricsApiService.php` (con fallback `/metrics/timeseries` → `/metrics`)
- `app/Services/HealthApiService.php` (estados up/degraded/down con latencia)

### ✅ Fase 2.5: Capa de validacion de formularios
- `app/Requests/FormRequestInterface.php` + `app/Requests/BaseFormRequest.php`
- Requests por dominio (`Auth`, `User`, `ApiKey`, `Profile`, `File`)
- `app/Config/Services.php` con factory `formRequest(...)`
- Controllers consumen `service('formRequest', ..., false)` en lugar de reglas inline
- `app/Controllers/BaseWebController.php` con helper `validateRequest()`
- Tests unitarios de normalizacion en `tests/unit/Requests/*`

### ✅ Fase 3: Layouts y componentes compartidos
- `app/Views/layouts/partials/head.php` — Tailwind CDN + Alpine CDN + Lucide CDN + tema
- `app/Views/layouts/auth.php`, `app.php` — layouts publico y autenticado
- Partials: `sidebar.php`, `navbar.php` (con selector de idioma), `flash_messages.php`
- Partials: `confirm_modal.php`, `pagination.php`, `remote_pagination.php`
- Partials: `filter_panel.php`, `table_toolbar.php`
- `public/assets/js/app.js` — Alpine stores (toasts, confirm modal)
- `app/Language/en/` y `app/Language/es/` — 10 archivos de idioma cada uno

### ✅ Fase 4: Autenticacion
- `app/Controllers/AuthController.php` — login, register, forgot/reset password, verify email, logout (revoca token via /auth/revoke)
- `app/Controllers/LanguageController.php` — cambio de idioma via session
- Vistas: `auth/login.php`, `register.php`, `forgot_password.php`, `reset_password.php`, `verify_email.php`
- `app/Config/Routes.php` — todas las rutas configuradas

### ✅ Fase 5: Dashboard, perfil y archivos
- `app/Controllers/DashboardController.php` — stats + health check del API
- `app/Controllers/ProfileController.php` — perfil (edicion admin), reset password por email, reenviar verificacion
- `app/Controllers/FileController.php` — upload (AJAX + progreso), list (data endpoint), download/view, delete
- Vistas correspondientes con tablas server-driven, filtros y previsualización de imágenes (Lightbox)

### ✅ Fase 6: Gestion de usuarios admin
- `app/Controllers/UserController.php` — CRUD completo + aprobar usuarios
- Flujo invitation-based (sin campo password en create/update)
- Vistas: `users/index.php`, `show.php`, `create.php`, `edit.php` + partials

### ✅ Fase 7: Auditoria admin
- `app/Controllers/AuditController.php` — listado, detalle, por entidad
- Vistas: `audit/index.php`, `show.php` + partials

### ✅ Fase 8: API Keys y Metricas admin
- `app/Controllers/ApiKeyController.php` — CRUD completo; muestra key generada una sola vez via session flash
- `app/Controllers/MetricsController.php` — summary + timeseries con resolucion de rango de fechas
- Vistas: `api_keys/` (4 vistas + partials), `metrics/index.php` + partials

### ✅ Fase 9: Error pages y polish
- `app/Views/errors/html/error_404.php`, `error_400.php`, `production.php`
- Soporte i18n completo en todas las vistas
- Cobertura de tests: unit (Libraries, Filters, Helpers, Services, Views) + feature (controller flows, filter enforcement)

---

## UI/UX Design - Sistema de Diseno Personalizable

### Principio: Todo configurable desde un solo lugar

El tema se define en `head.php` mediante CSS custom properties (variables CSS) + Tailwind config.
Cambiar marca = editar **1 archivo** (`head.php`): logo, colores, fuentes.

### Configuracion centralizada en `head.php`

```html
<!-- TEMA: Editar estas variables para cambiar toda la marca -->
<style>
  :root {
    /* Colores de marca - cambiar estos cambia TODO */
    --color-brand-50: 239 246 255;
    --color-brand-100: 219 234 254;
    --color-brand-200: 191 219 254;
    --color-brand-300: 147 197 253;
    --color-brand-400: 96 165 250;
    --color-brand-500: 59 130 246;
    --color-brand-600: 37 99 235;
    --color-brand-700: 29 78 216;
    --color-brand-800: 30 64 175;
    --color-brand-900: 30 58 138;

    /* Fuentes - cambiar aqui cambia toda la tipografia */
    --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, monospace;

    /* Logo/Nombre de la app */
    --app-name: 'API Client';
  }
</style>

<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        brand: {
          50: 'rgb(var(--color-brand-50) / <alpha-value>)',
          /* ... hasta 900, referenciando las CSS vars */
        }
      },
      fontFamily: {
        sans: ['var(--font-sans)'],
        mono: ['var(--font-mono)'],
      }
    }
  }
}
</script>
```

### Reglas de diseno

- **SIN gradientes decorativos** - Fondos solidos y limpios
- **Fondos**: `bg-white` para cards, `bg-gray-50` para fondo principal, `bg-gray-900` para sidebar
- **Layout auth**: Card blanco centrado sobre `bg-gray-50` (limpio, sin gradiente)
- **Colores de acento**: Solo `brand-600` para botones primarios, `brand-50` para hover suave en nav
- **Bordes sutiles**: `border-gray-200` para separacion, no sombras pesadas
- **Sombras**: Solo `shadow-sm` en cards, nada mas
- **Tipografia**: Font family via CSS var, facil swap (Inter, Poppins, lo que sea)
- **Logo**: Un slot en sidebar con `<img>` o texto, configurable

### Componentes base (clases Tailwind @layer)

```css
.btn-primary   { @apply bg-brand-600 hover:bg-brand-700 text-white ... }
.btn-secondary { @apply bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 ... }
.btn-danger    { @apply bg-red-600 hover:bg-red-700 text-white ... }
.form-input    { @apply border border-gray-300 focus:border-brand-500 focus:ring-brand-500 ... }
.card          { @apply bg-white border border-gray-200 rounded-lg shadow-sm p-6 }
```

### Interactividad Alpine.js
- Sidebar toggle, dropdowns, modals
- Drag-and-drop file upload
- Search debounce, password strength meter
- Toast auto-dismiss (5s)

### Responsive
- Mobile-first, sidebar overlay en mobile
- Tablas con scroll horizontal en mobile

### Estados UI
- Loading spinners en acciones
- Empty states con iconos y mensajes
- Error states inline en formularios

---

## Verificacion

1. Iniciar el API: `cd ci4-api-starter && php spark serve` (port 8080)
2. Iniciar el frontend: `cd ci4-admin-starter && php spark serve --port 8082`
3. (Opcional) Configurar API Key en `.env`: `apiClient.appKey = apk_...`
4. Probar flujos:
   - Registrar nuevo usuario en /register
   - Verificar que muestra estado "pending approval"
   - Login con credenciales validas/invalidas
   - Navegar dashboard, perfil, archivos
   - (Admin) Aprobar usuarios, CRUD usuarios, ver audit, metricas, gestionar API keys
   - Forgot password + reset password flow
   - Upload/download/delete archivos
   - Verificar auto-refresh de tokens (esperar >1hr o reducir TTL)
   - Verificar que rutas admin son inaccesibles para usuarios normales
5. Ejecutar tests: `vendor/bin/phpunit`
