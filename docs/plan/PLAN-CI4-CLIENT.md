# Plan: CI4 Admin Starter - Frontend Web Application

## Context

El proyecto `ci4-api-starter` es un REST API completo con 35 endpoints que cubre: autenticacion (JWT), gestion de usuarios, archivos, auditoria y metricas. Actualmente no existe una interfaz grafica para probar estos flujos. Se necesita una **nueva aplicacion CI4 independiente** que consuma este API y proporcione una UI moderna para validar todos los flujos de negocio.

## Decisiones del Usuario

- **Diseno**: Limpio y personalizable. Sin gradientes. CSS variables para colores/fuentes/logo. Cambiar marca = editar 1 archivo.
- **Alcance inicial**: Core primero (Fases 1-5): infraestructura, auth, dashboard, perfil y archivos (~35 archivos)
- **Fases 6-9** (admin: users, audit, metrics) se implementan despues

## Arquitectura General

```
Browser → CI4 Frontend App (port 8081) → CI4 API (port 8080)
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
│   │   ├── ApiClient.php              # Config: API_BASE_URL, timeouts
│   │   ├── Routes.php                 # Todas las rutas web
│   │   ├── Filters.php                # Registrar AuthFilter, AdminFilter
│   │   └── Autoload.php              # Autoload ui_helper
│   │
│   ├── Controllers/
│   │   ├── BaseWebController.php      # Base: acceso a ApiClient, viewData, helpers
│   │   ├── AuthController.php         # Login, register, forgot/reset password, verify email
│   │   ├── DashboardController.php    # Dashboard con stats
│   │   ├── ProfileController.php      # Ver/editar perfil, cambiar password
│   │   ├── UserController.php         # CRUD usuarios (admin)
│   │   ├── FileController.php         # Gestion de archivos
│   │   ├── AuditController.php        # Logs de auditoria (admin)
│   │   └── MetricsController.php      # Dashboard metricas (admin)
│   │
│   ├── Filters/
│   │   ├── AuthFilter.php             # Verifica session JWT, redirige a /login
│   │   └── AdminFilter.php            # Verifica role=admin
│   │
│   ├── Libraries/
│   │   └── ApiClient.php             # HTTP client central con auto-refresh JWT
│   │
│   ├── Services/
│   │   ├── AuthApiService.php         # Llamadas auth al API
│   │   ├── UserApiService.php         # Llamadas users al API
│   │   ├── FileApiService.php         # Llamadas files al API
│   │   ├── AuditApiService.php        # Llamadas audit al API
│   │   └── MetricsApiService.php      # Llamadas metrics al API
│   │
│   ├── Helpers/
│   │   └── ui_helper.php             # active_nav, format_date, status_badge, role_badge
│   │
│   └── Views/
│       ├── layouts/
│       │   ├── app.php                # Layout autenticado (sidebar + navbar)
│       │   ├── auth.php               # Layout publico (card centrado)
│       │   └── partials/
│       │       ├── head.php           # <head> comun: Tailwind CDN, Alpine CDN, theme
│       │       ├── sidebar.php        # Navegacion lateral colapsable
│       │       ├── navbar.php         # Barra superior con menu usuario
│       │       ├── flash_messages.php # Toasts de notificacion
│       │       ├── confirm_modal.php  # Modal de confirmacion reutilizable
│       │       └── pagination.php     # Componente paginacion
│       │
│       ├── auth/
│       │   ├── login.php              # Formulario login
│       │   ├── register.php           # Registro con indicador de fuerza de password
│       │   ├── forgot_password.php    # Solicitar reset de password
│       │   ├── reset_password.php     # Establecer nuevo password
│       │   └── verify_email.php       # Resultado de verificacion
│       │
│       ├── dashboard/
│       │   └── index.php              # Overview con stats cards
│       │
│       ├── profile/
│       │   └── index.php              # Ver/editar perfil + cambiar password
│       │
│       ├── users/
│       │   ├── index.php              # Tabla con search/filter/pagination
│       │   ├── show.php               # Detalle de usuario + historial audit
│       │   ├── create.php             # Formulario crear usuario
│       │   └── edit.php               # Formulario editar usuario
│       │
│       ├── files/
│       │   └── index.php              # File manager: upload + listado
│       │
│       ├── audit/
│       │   ├── index.php              # Tabla logs con filtros
│       │   └── show.php               # Detalle con diff old/new values
│       │
│       └── metrics/
│           └── index.php              # Dashboard metricas con graficos
│
├── public/
│   └── assets/js/
│       └── app.js                     # Alpine.js stores: toasts, confirm modal
│
└── .env                               # API_BASE_URL, session config
```

**Total: ~50 archivos nuevos**

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
$session->set('user', $data['user']); // {id, email, first_name, last_name, avatar_url, role}
```

- Tokens NUNCA se exponen al browser (solo en session PHP server-side)
- `AuthFilter` verifica existencia de `access_token` en session
- `AdminFilter` verifica `session('user.role') === 'admin'`

---

## Rutas

```php
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
POST /profile/change-password  → ProfileController::changePassword
POST /profile/resend-verification → ProfileController::resendVerification
GET  /files                    → FileController::index
POST /files/upload             → FileController::upload
GET  /files/{id}/download      → FileController::download
POST /files/{id}/delete        → FileController::delete

// --- Admin (filter: auth + admin) ---
GET  /admin/users              → UserController::index
GET  /admin/users/create       → UserController::create
POST /admin/users              → UserController::store
GET  /admin/users/{id}         → UserController::show
GET  /admin/users/{id}/edit    → UserController::edit
POST /admin/users/{id}         → UserController::update
POST /admin/users/{id}/delete  → UserController::delete
POST /admin/users/{id}/approve → UserController::approve
GET  /admin/audit              → AuditController::index
GET  /admin/audit/{id}         → AuditController::show
GET  /admin/audit/entity/{type}/{id} → AuditController::byEntity
GET  /admin/metrics            → MetricsController::index
```

---

## Fases de Implementacion (Core - Alcance Inicial)

### Fase 1: Setup del proyecto e infraestructura core
1. `composer create-project codeigniter4/appstarter ci4-admin-starter` en `/Users/davidcardenas/Developer/PHP/`
2. Configurar `.env` (API_BASE_URL=http://localhost:8080, session, app.baseURL=http://localhost:8081)
3. Crear `app/Config/ApiClient.php` - configuracion del cliente HTTP
4. Crear `app/Libraries/ApiClient.php` - cliente HTTP con auto-refresh JWT
5. Crear `app/Controllers/BaseWebController.php` - controller base
6. Crear `app/Filters/AuthFilter.php` y `app/Filters/AdminFilter.php`
7. Modificar `app/Config/Filters.php` - registrar filtros
8. Crear `app/Helpers/ui_helper.php` - helpers de vista
9. Modificar `app/Config/Autoload.php` - cargar ui_helper

### Fase 2: Capa de servicios API (solo los necesarios para core)
10. Crear `app/Services/AuthApiService.php`
11. Crear `app/Services/FileApiService.php`

### Fase 3: Layouts y componentes compartidos
12. Crear `app/Views/layouts/partials/head.php` - Tailwind CDN + Alpine CDN + tema
13. Crear `app/Views/layouts/auth.php` - layout publico (card centrado en gradiente)
14. Crear `app/Views/layouts/app.php` - layout autenticado (sidebar + navbar)
15. Crear `app/Views/layouts/partials/sidebar.php` - navegacion lateral colapsable
16. Crear `app/Views/layouts/partials/navbar.php` - barra superior con dropdown usuario
17. Crear `app/Views/layouts/partials/flash_messages.php` - toasts notificacion
18. Crear `app/Views/layouts/partials/confirm_modal.php` - modal confirmacion
19. Crear `app/Views/layouts/partials/pagination.php` - componente paginacion
20. Crear `public/assets/js/app.js` - Alpine stores (toasts, confirm)

### Fase 4: Autenticacion (flujo completo)
21. Crear `app/Controllers/AuthController.php` - login, register, forgot/reset, verify, logout
22. Crear `app/Views/auth/login.php` - email + password + links
23. Crear `app/Views/auth/register.php` - con indicador fuerza password (Alpine.js)
24. Crear `app/Views/auth/forgot_password.php` - email input
25. Crear `app/Views/auth/reset_password.php` - nuevo password con token
26. Crear `app/Views/auth/verify_email.php` - resultado verificacion
27. Configurar `app/Config/Routes.php` - todas las rutas

### Fase 5: Dashboard, perfil y archivos
28. Crear `app/Controllers/DashboardController.php`
29. Crear `app/Views/dashboard/index.php` - welcome card, stats, acciones rapidas
30. Crear `app/Controllers/ProfileController.php` - ver/editar + cambiar password
31. Crear `app/Views/profile/index.php` - perfil + cambiar password + verificacion email
32. Crear `app/Controllers/FileController.php` - upload, list, download, delete
33. Crear `app/Views/files/index.php` - drag-and-drop upload + grid archivos

**Total fase core: ~33 archivos nuevos/modificados**

### Fases Futuras (no incluidas en este alcance)
- Fase 6: Gestion de usuarios admin (UserController + 4 vistas)
- Fase 7: Auditoria admin (AuditController + 2 vistas)
- Fase 8: Metricas admin (MetricsController + 1 vista)
- Fase 9: Error pages y polish final

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
2. Iniciar el frontend: `cd ci4-admin-starter && php spark serve --port 8081`
3. Probar flujos:
   - Registrar nuevo usuario en /register
   - Verificar que muestra estado "pending approval"
   - Login con credenciales validas/invalidas
   - Navegar dashboard, perfil, archivos
   - (Admin) Aprobar usuarios, CRUD usuarios, ver audit, metricas
   - Forgot password + reset password flow
   - Upload/download/delete archivos
   - Verificar auto-refresh de tokens (esperar >1hr o reducir TTL)
   - Verificar que rutas admin son inaccesibles para usuarios normales
