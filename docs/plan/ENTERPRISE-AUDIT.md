# Plan de Readiness Empresarial — CI4 Admin Starter

**Fecha de auditoría:** 2026-04-13
**Branch analizado:** `dev`
**Veredicto:** 75% enterprise-ready — requiere ~24h de trabajo para publicación

---

## Resumen Ejecutivo

El proyecto tiene una arquitectura sólida, patrones maduros y convenciones bien establecidas. Sin embargo, **7 issues críticos** y **30 warnings** bloquean o degradan su calidad como template empresarial. Ninguno de estos problemas requiere refactors estructurales — todos son corregibles quirúrgicamente.

La auditoría cubrió: arquitectura de controladores y servicios, seguridad (sesiones, CSRF, CSP, cookies), tests y CI/CD, frontend/UX, i18n, y documentación.

---

## Índice

1. [Issues Críticos (C1–C7)](#1-issues-críticos)
2. [Warnings — Seguridad (W1–W7)](#2-warnings--seguridad)
3. [Warnings — Arquitectura (W8–W13)](#3-warnings--arquitectura)
4. [Warnings — Frontend y Accesibilidad (W14–W20)](#4-warnings--frontend-y-accesibilidad)
5. [Warnings — i18n (W21–W23)](#5-warnings--i18n)
6. [Warnings — CI/CD y Documentación (W24–W30)](#6-warnings--cicd-y-documentación)
7. [Fortalezas del Proyecto](#7-fortalezas-del-proyecto)
8. [Plan de Acción por Fases](#8-plan-de-acción-por-fases)
9. [Checklist de Verificación](#9-checklist-de-verificación)

---

## 1. Issues Críticos

> Estos issues DEBEN corregirse antes de publicar el proyecto como template empresarial.

---

### C1 — Session Fixation en Login

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Controllers/AuthController.php:219-225` |
| **Severidad** | 🔴 Crítico — vulnerabilidad de seguridad explotable |
| **Categoría** | Seguridad / Autenticación |

**Descripción:**
`persistAuthSession()` escribe los tokens JWT en la sesión existente sin regenerar el Session ID. Esto deja la aplicación vulnerable a ataques de _session fixation_: un atacante que controle o prediga el Session ID antes del login tendrá acceso a la sesión autenticada.

```php
// ACTUAL (vulnerable)
private function persistAuthSession(array $data): void
{
    $this->session->set('access_token', $data['access_token']);
    // ...
}

// CORRECTO
private function persistAuthSession(array $data): void
{
    $this->session->regenerate(true); // <- agregar esta línea primero
    $this->session->set('access_token', $data['access_token']);
    // ...
}
```

**Nota:** `ApiClient::clearSessionAuth()` ya llama `regenerate(true)` en el logout path — es una inconsistencia que solo falta en el login path.

---

### C2 — HTTP Header Injection en Content-Disposition

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Controllers/FileController.php:142-147` |
| **Severidad** | 🔴 Crítico — vulnerabilidad de inyección de headers |
| **Categoría** | Seguridad / Output Encoding |

**Descripción:**
El `$filename` en el header `Content-Disposition` viene de la respuesta de la API externa sin ningún saneamiento. Un valor malicioso como `"evil.exe"\r\nSet-Cookie: session=hijacked` podría:
- Inyectar headers HTTP adicionales
- Forzar descargas con nombres de archivo confusos
- Explotar vulnerabilidades en parsers de Content-Disposition

```php
// ACTUAL (vulnerable)
->setHeader('Content-Disposition', $disposition . '; filename="' . $filename . '"')

// CORRECTO
$safeFilename = str_replace(['"', "\r", "\n", "\0"], '', basename($filename));
->setHeader('Content-Disposition', $disposition . '; filename="' . $safeFilename . '"')
```

---

### C3 — CSRF Desactivado en `/login/google` sin Alternativa

| Campo | Detalle |
|-------|---------|
| **Archivos** | `app/Config/Filters.php:83`, `tests/feature/AuthGoogleLoginFlowTest.php:155-173` |
| **Severidad** | 🔴 Crítico — CSRF en ruta de autenticación |
| **Categoría** | Seguridad / CSRF |

**Descripción:**
El endpoint `/login/google` está excluido del filtro CSRF. El ID token de Google provee protección similar en producción (es de un solo uso y está vinculado al `client_id`), pero esta protección no está documentada ni verificada en el código. Adicionalmente, el único test que cubre el caso "Google login desactivado" (`testGoogleLoginDisabledRedirectsToLoginWithError`) está comentado, dejando ese path sin cobertura.

```php
// Filters.php:83 — exclusión sin documentación del por qué
'csrf' => ['except' => ['login/google']],
```

**Fix recomendado:**
1. Agregar un comentario explicando por qué se excluye y qué protección alternativa existe
2. Reactivar el test comentado en `AuthGoogleLoginFlowTest.php:155-173`
3. Agregar validación explícita de que el `id_token` proviene de Google (`iss`, `aud`, `exp` claims)

---

### C4 — CSP con `'unsafe-inline'` Anula Protección XSS

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Config/ContentSecurityPolicy.php:184,191` |
| **Severidad** | 🔴 Crítico — la CSP no provee protección XSS real |
| **Categoría** | Seguridad / Content Security Policy |

**Descripción:**
`'unsafe-inline'` en `scriptSrc` y `styleSrc` neutraliza completamente la CSP como defensa contra XSS. La presencia de nonces en CI4 es irrelevante mientras `'unsafe-inline'` esté listado: los navegadores modernos ignoran los nonces cuando `'unsafe-inline'` está presente.

```php
// ACTUAL (CSP inefectiva)
$this->scriptSrc = ["'self'", "'unsafe-inline'", 'https://cdn.tailwindcss.com', ...];
$this->styleSrc  = ["'self'", "'unsafe-inline'"];
```

**Fix recomendado:**
1. Eliminar `'unsafe-inline'` de ambas directivas
2. Activar `$this->autoNonce = true` (ya está en CI4)
3. Usar `csp_style_nonce()` y `csp_script_nonce()` en los helpers de vistas
4. Mover todos los scripts inline de las views a `app.js`
5. Mover todos los estilos inline críticos a un CSS separado

---

### C5 — Tailwind Play CDN (No Apto para Producción)

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Views/layouts/partials/head.php:7` |
| **Severidad** | 🔴 Crítico — rendimiento y estabilidad en producción |
| **Categoría** | Frontend / Performance |

**Descripción:**
`https://cdn.tailwindcss.com` es el _Play CDN_ de Tailwind — el build de desarrollo que genera clases en runtime mediante JavaScript. La documentación oficial de Tailwind lo dice explícitamente: _"not designed for production use"_. Problemas concretos:

- ~350 KB de JavaScript cargado y ejecutado en cada página
- No hay cache de CSS estático — el browser regenera estilos en cada carga
- Requiere acceso de escritura al DOM para inyectar estilos dinámicamente
- Incompatible con una CSP estricta sin `'unsafe-inline'`
- En redes lentas, las páginas muestran HTML sin estilos hasta que el JS carga

**Fix recomendado:**
1. Agregar `package.json` con `tailwindcss` como dev dependency
2. Crear `tailwind.config.js` con el `content` paths del proyecto
3. Crear `public/assets/css/app.css` con las directivas base
4. Compilar con `npx tailwindcss -i ./src/css/app.css -o ./public/assets/css/app.css`
5. Agregar step de build en CI antes de los tests
6. Reemplazar CDN por `<link rel="stylesheet" href="/assets/css/app.css">`

---

### C6 — Sin Coverage de Tests Automatizada en CI

| Campo | Detalle |
|-------|---------|
| **Archivos** | `phpunit.xml.dist`, `.github/workflows/ci.yml` |
| **Severidad** | 🔴 Crítico — calidad del template no verificable |
| **Categoría** | Testing / CI/CD |

**Descripción:**
El CI solo ejecuta `vendor/bin/phpunit` sin cobertura ni umbral mínimo. Varios módulos importantes no tienen tests:

| Módulo | Test existente | Gap |
|--------|---------------|-----|
| `DashboardController` | ❌ Ninguno | No se testa la agregación de 4 llamadas API |
| `MetricsController` | ❌ Ninguno | Solo hay unit test del service |
| `LanguageController` | ❌ Ninguno | Locale switching sin cobertura |
| `AdminFilter` | ❌ Ninguno | Role boundary logic sin unit test |
| `AuditController::show` | ❌ Ninguno | Ruta `/admin/audit/{id}` sin test |
| `AuditController::byEntity` | ❌ Ninguno | Ruta `/admin/audit/entity/{type}/{id}` sin test |
| Request validation negativa | ❌ Ninguno | Sin tests de formularios con inputs inválidos |
| `FileUploadRequest` | ⚠️ Parcial | No se testa MIME inválido ni archivo muy grande |

**Fix recomendado:**
1. Agregar bloque `<coverage>` en `phpunit.xml.dist` con threshold de líneas (~70%)
2. Escribir `tests/feature/DashboardFlowTest.php`
3. Escribir `tests/feature/MetricsFlowTest.php`
4. Escribir `tests/feature/LanguageFlowTest.php`
5. Escribir `tests/unit/Filters/AdminFilterTest.php`
6. Agregar tests de path negativo a los requests existentes
7. Agregar step de coverage report en CI (`--coverage-clover`)

---

### C7 — Sin Análisis Estático en CI

| Campo | Detalle |
|-------|---------|
| **Archivo** | `.github/workflows/ci.yml` |
| **Severidad** | 🔴 Crítico — errores de tipo y calidad no detectados |
| **Categoría** | CI/CD / Code Quality |

**Descripción:**
El pipeline no ejecuta ningún análisis estático. Issues que quedan sin detectar:

- `ApiClientInterface` declara todos los métodos con tipo de retorno `array` sin shape — errores de acceso a keys incorrectas no se detectan
- `app.js` tiene 1,009 líneas sin ningún linter ni test
- Variable shadowing (`text`) en `app.js:655` vs `:380` — funciona pero es una trampa de mantenimiento

**Fix recomendado:**
```yaml
# .github/workflows/ci.yml — agregar estos steps
- name: Static Analysis (PHPStan)
  run: vendor/bin/phpstan analyse --level=5

- name: Code Style (PHP CS Fixer)
  run: vendor/bin/php-cs-fixer fix --dry-run --diff

- name: JS Lint (ESLint)
  run: npx eslint public/assets/js/app.js
```

Agregar `phpstan.neon` al proyecto:
```neon
parameters:
    level: 5
    paths:
        - app
    excludePaths:
        - app/Views
```

---

## 2. Warnings — Seguridad

---

### W1 — `regenerateDestroy = false` en Session

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Config/Session.php:92` |
| **Severidad** | ⚠️ Warning — window de vulnerabilidad post-regeneración |

Cuando el Session ID se regenera cada 300s, la sesión antigua permanece válida hasta que el GC la elimina. Un token de sesión robado podría usarse en esa ventana.

**Fix:** `public bool $regenerateDestroy = true;`

---

### W2 — Mensajes de Error Hardcodeados en Filtros

| Campo | Detalle |
|-------|---------|
| **Archivos** | `app/Filters/AuthFilter.php:17`, `app/Filters/AdminFilter.php:25` |
| **Severidad** | ⚠️ Warning — rompe i18n |

Los mensajes de redirect están en español literal en lugar de usar `lang()`:

```php
// ACTUAL
session()->setFlashdata('error', 'Tu sesion expiro. Inicia sesion.');

// CORRECTO
session()->setFlashdata('error', lang('Auth.sessionExpired'));
```

Requiere agregar las keys `sessionExpired` y `noPermission` a `app/Language/en/Auth.php` y `app/Language/es/Auth.php`.

---

### W3 — Log Debug Expone Estructura de la Sesión

| Campo | Detalle |
|-------|---------|
| **Archivos** | `app/Filters/AuthFilter.php:16`, `app/Filters/AdminFilter.php:25` |
| **Severidad** | ⚠️ Warning — fuga de información en logs |

```php
// Esta línea registra todos los key names de la sesión en cada redirect
log_message('debug', '...Current session keys: ' . implode(', ', array_keys(session()->get() ?? [])));
```

En entornos donde `log_threshold` incluye `debug` (desarrollo y staging), cualquier acceso al `writable/logs/` expone la estructura interna de la sesión.

**Fix:** Eliminar o reducir a `log_message('debug', 'AuthFilter: no access_token in session')`.

---

### W4 — `token_expires_at` No Se Valida en Filtros

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Filters/AuthFilter.php` |
| **Severidad** | ⚠️ Warning — degradación de UX en tokens expirados |

El valor `token_expires_at` se guarda en sesión (`AuthController.php:223`) pero `AuthFilter` solo verifica que `access_token` exista. Si el token expiró, el filtro pasa, el controlador dispara la llamada API, el `ApiClient` recibe 401, intenta un refresh, y si ese también falla, el usuario recibe un error genérico de "conexión" en lugar de un redirect limpio a login.

**Fix:** En `AuthFilter::before()`, validar proactivamente:
```php
$expiresAt = session('token_expires_at');
if ($expiresAt && $expiresAt < time()) {
    // Token expirado — forzar refresh antes de continuar, o destruir sesión
}
```

---

### W5 — Sin `reportURI` en CSP

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Config/ContentSecurityPolicy.php:31` |
| **Severidad** | ⚠️ Warning — violaciones CSP silenciosas |

Sin `reportURI`, cualquier violación de la CSP es silenciosamente descartada por el browser. En producción, esto hace imposible detectar ataques XSS en curso o regresiones de la política.

**Fix:** Configurar un endpoint de reporte o usar un servicio externo (e.g., `https://report-uri.com`):
```php
public string $reportURI = ''; // configurar vía env var
```

---

### W6 — CSRF Token No Se Regenera en Cada Submit

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Config/Security.php:74` |
| **Severidad** | ⚠️ Warning — ventana de reutilización de token |

`$regenerate = false` significa que el token CSRF es válido para toda la sesión, no solo para un submit. Un token capturado (e.g., vía shoulder surfing o referer header) puede reutilizarse.

**Nota:** `tokenRandomize = true` mitiga parcialmente esto, pero para un panel de administración `$regenerate = true` es la recomendación.

---

### W7 — Filtros de Seguridad Adicionales Comentados

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Config/Filters.php:81,84` |
| **Severidad** | ⚠️ Warning — defensa en profundidad incompleta |

```php
// Comentados — no activos:
// 'honeypot',
// 'invalidchars',
```

`invalidchars` bloquea caracteres de control en requests que podrían usarse para XSS y path traversal. Para un panel admin, activarlo proporciona una capa adicional de defensa.

---

## 3. Warnings — Arquitectura

---

### W8 — Sesión Acoplada al Constructor de `ApiClient`

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Libraries/ApiClient.php:24` |
| **Severidad** | ⚠️ Warning — testabilidad y flexibilidad |

```php
public function __construct(Config $config)
{
    $this->session = session(); // <- acoplamiento en construcción
```

`session()` en el constructor hace que `ApiClient` sea inutilizable fuera de un web request (CLI commands, queue workers, tests sin HTTP layer). La sesión debería inyectarse o resolverse lazy.

---

### W9 — `UserController::edit()` No Tiene Path de Error

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Controllers/UserController.php:85-94` |
| **Severidad** | ⚠️ Warning — UX degradada en fallo de API |

Si `safeApiCall` en `edit()` retorna `ok: false`, `extractData($response)` devuelve `[]` y el formulario de edición se renderiza vacío sin ningún mensaje de error. A diferencia de `show()` que usa `renderResourceShow()`, `edit()` no tiene fallback.

**Fix:** Agregar el mismo patrón de `show()`:
```php
if (! $response['ok']) {
    return $this->renderResourceShow('users/edit', $editUser ?? [], lang('Users.notFound'));
}
```

---

### W10 — `token_expires_at` Guardado pero Nunca Validado Proactivamente

| Campo | Detalle |
|-------|---------|
| **Archivos** | `app/Controllers/AuthController.php:223`, `app/Libraries/ApiClient.php:216` |
| **Severidad** | ⚠️ Warning — comportamiento solo reactivo |

El sistema solo sabe que un token expiró cuando la API retorna 401. Si la API no es consistente en retornar 401s (e.g., en endpoints que cachean), el token podría estar expirado sin que la app lo detecte.

---

### W11 — `ext-curl` en `suggest` en lugar de `require`

| Campo | Detalle |
|-------|---------|
| **Archivo** | `composer.json:29` |
| **Severidad** | ⚠️ Warning — fallo en runtime no detectado en install |

`CURLRequest` (usado por `ApiClient`) requiere `ext-curl`. Si no está disponible, cada llamada API falla en runtime con un error crítico. Debería estar en `require` para que `composer install` lo detecte.

---

### W12 — `ext-fileinfo` en `suggest` pero Usado en Runtime

| Campo | Detalle |
|-------|---------|
| **Archivos** | `composer.json:39`, `app/Services/FileApiService.php:31` |
| **Severidad** | ⚠️ Warning — fallo en runtime no detectado en install |

`FileApiService::upload()` llama `new \finfo(FILEINFO_MIME_TYPE)` cuando `$mimeType` es null. Sin `ext-fileinfo`, esto falla silenciosamente.

---

### W13 — `apiPrefix` Hardcodeado

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Config/ApiClient.php:15` |
| **Severidad** | ⚠️ Warning — falta flexibilidad de configuración |

`public string $apiPrefix = '/api/v1';` no tiene variable de entorno correspondiente. Si la API backend cambia su prefijo, requiere un cambio de código en lugar de configuración.

**Fix:** Agregar `API_PREFIX` al `.env.example` y leerlo en la config.

---

## 4. Warnings — Frontend y Accesibilidad

---

### W14 — Alpine.js CDN Sin Pin de Versión

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Views/layouts/partials/head.php:8` |
| **Severidad** | ⚠️ Warning — estabilidad del template |

```html
<!-- ACTUAL — versión flotante -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- CORRECTO — versión pinada -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"
        integrity="sha384-..." crossorigin="anonymous"></script>
```

Una actualización breaking en Alpine.js 3.x podría romper toda la interactividad del template sin ningún aviso.

---

### W15 — Sin SRI Hashes en CDN Scripts

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Views/layouts/partials/head.php:7-9` |
| **Severidad** | ⚠️ Warning — riesgo de supply-chain |

Los tres CDN (Tailwind, Alpine.js, Lucide) se cargan sin atributos `integrity`. Si cualquiera de estos CDN es comprometido, se podría entregar JavaScript malicioso que la CSP permitiría (ya que el dominio está en la whitelist).

**Fix:** Generar SRI hashes con `openssl dgst -sha384 -binary file.js | openssl base64 -A` y agregar a cada `<script>` e `<link>`.

---

### W16 — Modal de Confirmación No es Accesible

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Views/layouts/partials/confirm_modal.php` |
| **Severidad** | ⚠️ Warning — falla WCAG 2.1 AA |

El modal carece de:
- `role="dialog"` en el contenedor
- `aria-modal="true"` para indicar que el contenido detrás es inerte
- `aria-labelledby` apuntando al `<h3>` del título
- Focus trap — tabulando mientras el modal está abierto se accede al contenido detrás

Esto haría fallar una auditoría de accesibilidad empresarial estándar.

---

### W17 — Botón Close del Sidebar Sin `aria-label`

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Views/layouts/partials/sidebar.php:5` |
| **Severidad** | ⚠️ Warning — accesibilidad para lectores de pantalla |

```html
<!-- ACTUAL — lectores de pantalla anuncian "x, botón" -->
<button @click="sidebarOpen = false">x</button>

<!-- CORRECTO -->
<button @click="sidebarOpen = false" aria-label="Cerrar navegación">
  <!-- icono -->
</button>
```

---

### W18 — Sin "Skip to Main Content" Link

| Campo | Detalle |
|-------|---------|
| **Archivos** | `app/Views/layouts/app.php`, `app/Views/layouts/auth.php` |
| **Severidad** | ⚠️ Warning — WCAG 2.4.1 Level A (requisito mínimo) |

Los usuarios de teclado deben tabular a través de toda la navegación lateral en cada página para llegar al contenido principal. WCAG 2.4.1 es un criterio Level A, el nivel mínimo de conformidad.

**Fix:**
```html
<!-- Primera línea del <body> en app.php -->
<a href="#main-content" class="sr-only focus:not-sr-only">Saltar al contenido principal</a>
<!-- ... -->
<main id="main-content">
```

---

### W19 — Variable Shadowing `text` en `app.js`

| Campo | Detalle |
|-------|---------|
| **Archivo** | `public/assets/js/app.js:655` vs `:380` |
| **Severidad** | ⚠️ Warning — trampa de mantenimiento |

```javascript
// Línea 380: outer scope
const text = uiLabels[locale]; // { loadRetry: 'Reintentar', ... }

// Línea 655: dentro de fetchData() try block
const text = await response.text(); // sombrea la anterior

// Línea 704: catch block — aquí text se refiere al OUTER (uiLabels)
this.errorMessage = text.loadRetry; // funciona accidentalmente
```

El código funciona porque el `catch` accede al `text` del scope exterior, no del `try`. Si alguien refactoriza moviendo el `catch` o la declaración, se romperá silenciosamente.

**Fix:** Renombrar la variable interna: `const responseText = await response.text();`

---

### W20 — `Alpine.store('toast')` Sin View Template

| Campo | Detalle |
|-------|---------|
| **Archivo** | `public/assets/js/app.js:406-418` |
| **Severidad** | ⚠️ Warning — funcionalidad invisible |

El store de toast está definido e inicializado, pero ninguna view en los layouts renderiza `$store.toast.items`. Cualquier código que llame `$store.toast.push({...})` actualizará el store sin producir ninguna notificación visible al usuario.

---

## 5. Warnings — i18n

---

### W21 — String Hardcodeado en Inglés en Dashboard

| Campo | Detalle |
|-------|---------|
| **Archivo** | `app/Views/dashboard/index.php:141` |
| **Severidad** | ⚠️ Warning — texto en inglés para usuarios español |

```php
// ACTUAL
echo "No recent activity detected.";

// CORRECTO
echo lang('Dashboard.noRecentActivity');
// + agregar key en en/Dashboard.php y es/Dashboard.php
```

---

### W22 — Labels de JS en Español Sin Tildes

| Campo | Detalle |
|-------|---------|
| **Archivo** | `public/assets/js/app.js:153-165` |
| **Severidad** | ⚠️ Warning — calidad de texto para usuarios finales |

Los labels del locale `es` en `app.js` tienen errores tipográficos respecto a los archivos PHP correspondientes:

| JS (incorrecto) | PHP (correcto) |
|-----------------|----------------|
| `'Confirmar accion'` | `'Confirmar acción'` |
| `'La solicitud fallo'` | `'La solicitud falló'` |
| `'No se pudo cargar la informacion'` | `'No se pudo cargar la información'` |

---

### W23 — Maps i18n de JS No Sincronizados con PHP

| Campo | Detalle |
|-------|---------|
| **Archivo** | `public/assets/js/app.js:152-265` |
| **Severidad** | ⚠️ Warning — riesgo de divergencia silenciosa |

Los mapas de labels (`statusLabels`, `roleLabels`, `auditActionLabels`, etc.) son copias manuales de los archivos PHP de idioma. Agregar un nuevo estado, rol o acción al backend requiere actualizar:
1. `app/Language/en/Users.php`
2. `app/Language/es/Users.php`
3. Los objetos `en` y `es` en `app.js`

No hay mecanismo para detectar cuando estas fuentes divergen. Una solución es generar los labels desde PHP y pasarlos al DOM como `data-*` o un JSON script tag.

---

## 6. Warnings — CI/CD y Documentación

---

### W24 — PHP Version Fija en CI (Sin Matrix)

| Campo | Detalle |
|-------|---------|
| **Archivo** | `.github/workflows/ci.yml:16` |
| **Severidad** | ⚠️ Warning — compatibilidad no verificada |

```yaml
# ACTUAL — solo PHP 8.2
- uses: shivammathur/setup-php@v2
  with:
    php-version: '8.2'

# RECOMENDADO — matrix de versiones
strategy:
  matrix:
    php-version: ['8.2', '8.3']
```

---

### W25 — Sin Docker / docker-compose

| Campo | Detalle |
|-------|---------|
| **Severidad** | ⚠️ Warning — onboarding friction |

Sin `docker-compose.yml`, los desarrolladores deben configurar manualmente PHP, extensiones, y composer. Para un template empresarial, un `docker-compose up` que levante la app completa reduce significativamente el tiempo de onboarding.

**Sugerencia mínima:**
```yaml
# docker-compose.yml
services:
  app:
    image: php:8.2-apache
    volumes:
      - .:/var/www/html
    ports:
      - "8082:80"
```

---

### W26 — Discrepancia en Documentación de Upload

| Campo | Detalle |
|-------|---------|
| **Archivo** | `docs/FLUJOS-CRITICOS.md` |
| **Severidad** | ⚠️ Warning — puede confundir integradores |

`FLUJOS-CRITICOS.md` describe el upload como "Base64 es el método principal", pero la implementación actual en `app/Views/files/index.php` usa `<form enctype="multipart/form-data">` con XHR. La documentación del flujo crítico debe reflejar el mecanismo real.

---

### W27 — Sin `SECURITY.md`

| Campo | Detalle |
|-------|---------|
| **Severidad** | ⚠️ Warning — ausencia de proceso de divulgación responsable |

Un template empresarial debería incluir:
- Cómo reportar vulnerabilidades
- El modelo de amenaza del template
- Responsabilidades de seguridad al hacer fork
- Lista de dependencias de terceros y su estado de seguridad

---

### W28 — Plan Doc No Actualizado Post-Implementación

| Campo | Detalle |
|-------|---------|
| **Archivo** | `docs/plan/PLAN-CI4-CLIENT.md` |
| **Severidad** | ⚠️ Warning — confunde estado actual con roadmap |

El documento aún usa lenguaje de roadmap ("Fase 6 — próximos pasos") cuando el proyecto está completamente implementado. Debería actualizarse para reflejar el estado actual como referencia arquitectónica, no como plan futuro.

---

### W29 — Sin CHANGELOG ni Política de Versioning

| Campo | Detalle |
|-------|---------|
| **Severidad** | ⚠️ Warning — mantenibilidad de forks |

Sin CHANGELOG, los equipos que hagan fork del template no tienen forma de saber qué cambió entre versiones. Sin política de versioning (`MAJOR.MINOR.PATCH`), no pueden evaluar si deben incorporar cambios upstream.

---

### W30 — Credenciales Reales en `.env` Local

| Campo | Detalle |
|-------|---------|
| **Archivo** | `.env` (no trackeado, correcto) |
| **Severidad** | ⚠️ Warning — riesgo de exposición accidental |

El `.env` local contiene un `GOOGLE_CLIENT_ID` real y un `apiClient.appKey` real. Aunque el `.gitignore` los excluye correctamente, `git add -f .env` los expondría. Si el repositorio se va a hacer público, se recomienda rotar esas credenciales antes.

---

## 7. Fortalezas del Proyecto

Estos patrones están bien implementados y deben preservarse y documentarse como decisiones arquitectónicas intencionales.

### Arquitectura

| Fortaleza | Ubicación | Por qué es valiosa |
|-----------|-----------|-------------------|
| `safeApiCall()` + `failApi()` | `BaseWebController.php:223,91` | Wrapper defensivo consistente en todos los controllers — un solo punto de manejo de errores |
| `ResourceApiService` template method | `app/Services/ResourceApiService.php` | CRUD services de 10 líneas con extensibilidad limpia |
| `resolveTableState()` con whitelist | `BaseWebController.php:373-474` | Previene parameter injection en filtros y sorts de tablas |
| Token refresh en 401 con retry | `ApiClient.php:135-148` | Transparente para el usuario — la sesión se extiende automáticamente |
| `redactData()` para logs | `ApiClient.php:420-455` | Log hygiene de producción — nunca loggea tokens o datos sensibles |
| Double-filter `['auth', 'admin']` | `Routes.php:40` | Admin routes siempre requieren ambos filtros — no hay forma de olvidar uno |
| `ApiClientInterface` con DI | `Services/BaseApiService.php` | Tests unitarios sin HTTP real son posibles gracias a la interfaz |

### Seguridad

| Fortaleza | Evidencia |
|-----------|-----------|
| JWT solo en PHP session | Verificado en `app.js` — cero llamadas `Authorization` construidas en JS |
| CSRF session-mode | `Security.php:18` — más seguro que cookie-mode para apps con sesiones |
| HttpOnly + Secure + SameSite | `Cookie.php:57,66,90` — los tres flags críticos están activos |
| `secureheaders` global | `Filters.php:88` — aplica en todas las respuestas |
| Logout via POST con CSRF | `navbar.php:32-35` — patrón frecuentemente omitido que aquí está correcto |
| Sin DB directa | Arquitectura API-proxy — elimina SQLi como clase de vulnerabilidad |

### Frontend

| Fortaleza | Evidencia |
|-----------|-----------|
| 432+ llamadas `esc()` | Contadas en todas las views — escaping XSS consistente |
| ARIA live regions en toasts | `flash_messages.php:2` — `polite`/`assertive` correcto |
| `aria-sort` en columnas | `users/index.php:46` — implementación completa |
| Race condition guard | `app.js:641-710` — `requestId` pattern previene datos stale |
| `encodeURIComponent()` en URLs | `app.js:980-998` — URLs con datos del API siempre escapadas |

### i18n

| Fortaleza | Evidencia |
|-----------|-----------|
| Simetría perfecta `en/es` | 13 archivos × 2 idiomas con keys idénticas |
| RFC 4647 Accept-Language parsing | `LocaleFilter.php:51-83` — quality-factor completo con fallback base-locale |

### DevOps

| Fortaleza | Evidencia |
|-----------|-----------|
| `install.sh` polished | Detecta OS, valida inputs, preview antes de aplicar, actualiza 10+ archivos |
| `.gitignore` correcto | `.env` excluido, `.env.example` whitelisted |
| CI existente | `.github/workflows/ci.yml` — tests en cada push/PR |
| `failOnRisky + failOnWarning` | `phpunit.xml.dist:8-9` — suite estricta |

---

## 8. Plan de Acción por Fases

---

### Fase 1 — Seguridad Bloqueante (~4h)

> Objetivo: corregir vulnerabilidades explotables antes de cualquier deploy.

| # | Tarea | Archivo | Esfuerzo |
|---|-------|---------|---------|
| 1.1 | Agregar `$this->session->regenerate(true)` en `persistAuthSession()` | `AuthController.php:219` | 15min |
| 1.2 | Sanitizar `$filename` en `Content-Disposition` | `FileController.php:142-147` | 15min |
| 1.3 | Documentar por qué `/login/google` está excluido del CSRF y agregar validación explícita de claims del ID token | `Filters.php:83`, `AuthController.php` | 1h |
| 1.4 | Reactivar test comentado `testGoogleLoginDisabledRedirectsToLoginWithError` | `AuthGoogleLoginFlowTest.php:155` | 30min |
| 1.5 | `$regenerateDestroy = true` en Session config | `Session.php:92` | 5min |
| 1.6 | Traducir mensajes flash de AuthFilter/AdminFilter a `lang()` | `AuthFilter.php:17`, `AdminFilter.php:25` | 30min |
| 1.7 | Agregar keys `sessionExpired` y `noPermission` a ambos idiomas | `Language/en/Auth.php`, `Language/es/Auth.php` | 15min |
| 1.8 | Eliminar/reducir logs debug que exponen session keys | `AuthFilter.php:16`, `AdminFilter.php:25` | 15min |
| 1.9 | Mover `ext-curl` y `ext-fileinfo` de `suggest` a `require` | `composer.json` | 5min |

---

### Fase 2 — CSP y CDN (~6h)

> Objetivo: protección XSS real y rendimiento de producción.

| # | Tarea | Archivo | Esfuerzo |
|---|-------|---------|---------|
| 2.1 | Agregar `package.json` con `tailwindcss` como dev dependency | Nuevo archivo | 30min |
| 2.2 | Crear `tailwind.config.js` con content paths del proyecto | Nuevo archivo | 30min |
| 2.3 | Crear `public/assets/css/app.css` compilado | `head.php:7` → `<link>` | 1h |
| 2.4 | Agregar step de build Tailwind en CI | `ci.yml` | 30min |
| 2.5 | Quitar `'unsafe-inline'` de `scriptSrc` y `styleSrc` | `ContentSecurityPolicy.php:184,191` | 30min |
| 2.6 | Activar `$autoNonce = true` y usar `csp_script_nonce()` en views | `ContentSecurityPolicy.php`, `head.php` | 1h |
| 2.7 | Mover scripts inline de views a `app.js` (Alpine `x-data` configs) | Views afectadas | 1h |
| 2.8 | Pin Alpine.js a versión exacta con SRI hash | `head.php:8` | 30min |
| 2.9 | Agregar SRI hash a Lucide Icons CDN | `head.php:9` | 15min |

---

### Fase 3 — Tests y CI (~8h)

> Objetivo: coverage automatizado y análisis estático en el pipeline.

| # | Tarea | Archivo | Esfuerzo |
|---|-------|---------|---------|
| 3.1 | Escribir `DashboardFlowTest.php` (happy path + partial API failures) | `tests/feature/` | 1.5h |
| 3.2 | Escribir `MetricsFlowTest.php` (render, filtros, sin datos) | `tests/feature/` | 1h |
| 3.3 | Escribir `LanguageFlowTest.php` (set locale, persist en sesión) | `tests/feature/` | 45min |
| 3.4 | Escribir `AdminFilterTest.php` (allow admin, block non-admin, AJAX 403) | `tests/unit/Filters/` | 1h |
| 3.5 | Agregar tests para `AuditController::show` y `byEntity` | `tests/feature/AuditFlowTest.php` | 1h |
| 3.6 | Agregar tests negativos a `UserStoreRequest`, `LoginRequest`, `FileUploadRequest` | `tests/unit/Requests/` | 1h |
| 3.7 | Agregar `<coverage>` con threshold en `phpunit.xml.dist` | `phpunit.xml.dist` | 15min |
| 3.8 | Instalar y configurar PHPStan (nivel 5) | `phpstan.neon`, `composer.json` | 45min |
| 3.9 | Agregar ESLint al proyecto | `package.json`, `.eslintrc.json` | 30min |
| 3.10 | Agregar steps de static analysis y coverage a CI | `ci.yml` | 30min |
| 3.11 | Agregar matrix PHP 8.2 + 8.3 en CI | `ci.yml` | 15min |

---

### Fase 4 — Accesibilidad y UX (~4h)

> Objetivo: conformidad WCAG 2.1 AA mínima para uso enterprise.

| # | Tarea | Archivo | Esfuerzo |
|---|-------|---------|---------|
| 4.1 | Agregar `role="dialog"`, `aria-modal`, `aria-labelledby` al confirm_modal | `confirm_modal.php` | 30min |
| 4.2 | Implementar focus trap en el modal con Alpine.js | `confirm_modal.php`, `app.js` | 1h |
| 4.3 | Agregar `aria-label` al botón close del sidebar | `sidebar.php:5` | 10min |
| 4.4 | Agregar `aria-expanded` al botón hamburger del navbar | `navbar.php:3` | 15min |
| 4.5 | Agregar skip-to-main-content link en ambos layouts | `app.php`, `auth.php` | 20min |
| 4.6 | Agregar `id="main-content"` al `<main>` en ambos layouts | `app.php`, `auth.php` | 5min |
| 4.7 | Implementar view template para `Alpine.store('toast')` en layouts | `app.php` o nuevo partial | 45min |
| 4.8 | Corregir string hardcodeado `"No recent activity detected."` | `dashboard/index.php:141` | 15min |
| 4.9 | Corregir tildes faltantes en labels ES de `app.js` | `app.js:153-165` | 15min |
| 4.10 | Renombrar `const text` a `const responseText` en `fetchData()` | `app.js:655` | 10min |

---

### Fase 5 — Documentación y DevOps (~2h)

> Objetivo: documentación completa para onboarding y mantenimiento de forks.

| # | Tarea | Archivo | Esfuerzo |
|---|-------|---------|---------|
| 5.1 | Sincronizar `FLUJOS-CRITICOS.md` — upload section con implementación real | `docs/FLUJOS-CRITICOS.md` | 30min |
| 5.2 | Crear `SECURITY.md` con modelo de amenaza, reporte de vulnerabilidades | Nuevo archivo | 30min |
| 5.3 | Actualizar `PLAN-CI4-CLIENT.md` como referencia histórica (sin roadmap activo) | `docs/plan/PLAN-CI4-CLIENT.md` | 20min |
| 5.4 | Crear `CHANGELOG.md` con la entrada inicial | Nuevo archivo | 20min |
| 5.5 | Agregar sección "Production Deployment Checklist" al README | `README.md` | 20min |
| 5.6 | Agregar `API_PREFIX` como variable de entorno configurable | `Config/ApiClient.php`, `.env.example` | 20min |

---

## 9. Checklist de Verificación

Usar este checklist para validar que todos los items críticos están resueltos antes de publicar el template.

### Seguridad
- [ ] **C1** — Login llama `$session->regenerate(true)` antes de `set()`
- [ ] **C2** — `Content-Disposition` filename sanitizado con `basename()` y strip de caracteres de control
- [ ] **C3** — `/login/google` CSRF excepción documentada; test `testGoogleLoginDisabledRedirectsToLoginWithError` activo
- [ ] **C4** — CSP sin `'unsafe-inline'`; nonces activos; verificado con [CSP Evaluator](https://csp-evaluator.withgoogle.com/)
- [ ] **W1** — `regenerateDestroy = true`
- [ ] **W2** — Mensajes de filtros usan `lang()`
- [ ] **W3** — Logs debug eliminados de filtros

### Performance
- [ ] **C5** — Tailwind compilado a CSS estático; Play CDN removido; página carga sin JS de Tailwind

### Tests
- [ ] **C6** — `phpunit.xml.dist` tiene `<coverage>` con threshold; CI reporta coverage
- [ ] **C6** — Tests nuevos: Dashboard, Metrics, Language, AdminFilter, AuditController paths
- [ ] **C7** — PHPStan nivel 5 pasa sin errores en CI
- [ ] **C7** — php-cs-fixer `--dry-run` pasa en CI
- [ ] **C7** — ESLint pasa en `app.js`

### Accesibilidad
- [ ] **W16** — Modal pasa validación con Axe o Lighthouse Accessibility
- [ ] **W18** — Skip link presente y funcional con Tab key
- [ ] **W17** — Sidebar close button tiene `aria-label`

### Documentación
- [ ] **W26** — Doc de upload sincronizada con implementación real
- [ ] **W27** — `SECURITY.md` presente en root del repo
- [ ] **W29** — `CHANGELOG.md` presente con versión inicial

---

*Documento generado por auditoría automatizada. Última actualización: 2026-04-13.*
