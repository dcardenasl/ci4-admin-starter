# CI4 Admin Starter

Una **plantilla de panel administrativo CodeIgniter 4 lista para producción** diseñada para consumir e interactuar con la API backend [ci4-api-starter](https://github.com/dcardenasl/ci4-api-starter).

## 🎯 Propósito

Este es un **frontend renderizado en servidor** (SRF) que proporciona un panel administrativo completo. NO implementa lógica de negocio ni acceso directo a base de datos—es una aplicación cliente que orquesta solicitudes HTTP a una API backend y renderiza vistas en servidor.

**Diseño Arquitectónico:**
```
Navegador → CI4 Admin Starter (este repo) → API Backend (ci4-api-starter)
```

## 📋 Principios Clave

1. **Arquitectura Desacoplada:** La lógica de negocio y persistencia viven en el backend (`ci4-api-starter`). Este frontend es sin estado excepto por el almacenamiento JWT en sesión.
2. **Vistas Renderizadas en Servidor:** Usa vistas PHP con Tailwind CSS y Alpine.js para interactividad. No requiere pipeline de construcción frontend para producción.
3. **Comunicación API Centralizada:** Todas las solicitudes HTTP pasan por `app/Libraries/ApiClient.php`, que maneja refresco de tokens, manejo de errores y normalización de respuestas.
4. **Patrón de Capa de Servicios:** Los controladores llaman a Servicios, que usan ApiClient. Mantiene el código organizado y comprobable.
5. **Validación FormRequest:** La validación de formularios está centralizada en clases dentro de cada módulo (`app/Modules/{ModuleName}/Requests/`), manteniendo los controladores delgados.

## ⚡ Inicio Rápido

Para configuración inicial, ver **[GUÍA INICIO RÁPIDO](./QUICK-START.md)** para instrucciones paso a paso.

**TL;DR:**
```bash
# 1. Clonar e instalar
bash install.sh

# 2. Iniciar servidores de desarrollo (dos terminales)
php spark serve --port 8182    # Terminal 1
npm run dev:css                # Terminal 2

# 3. Abrir en navegador
# http://localhost:8182
```

## 📚 Documentación

La documentación completa está disponible en el **[Hub de Documentación](./INDEX.md)**. Temas clave:

| Guía | Propósito |
|------|-----------|
| **[Inicio Rápido](./QUICK-START.md)** | Configuración inicial y verificación |
| **[Arquitectura](./ARCHITECTURE.md)** | Diseño del sistema, ApiClient, patrones de seguridad |
| **[Servicios y Validación](./SERVICES.md)** | Capa de servicios, patrón FormRequest |
| **[Guía Frontend](./FRONTEND.md)** | Componentes UI, Tailwind, Alpine.js |
| **[Pruebas](./TESTING.md)** | Estrategias de pruebas unitarias y de características |
| **[Despliegue](./DEPLOYMENT.md)** | Checklist de producción y configuración |
| **[Resolución de Problemas](./TROUBLESHOOTING.md)** | Problemas comunes y soluciones |
| **[Preguntas Frecuentes](./FAQ.md)** | Preguntas frecuentes |

## 🔐 Contrato API e Integración Backend

Esta plantilla está diseñada para trabajar sin interrupciones con [ci4-api-starter](https://github.com/dcardenasl/ci4-api-starter). El contrato del backend es obligatorio:

- **Prefijo API:** `/api/v1`
- **Autenticación:** Bearer JWT con manejo automático de token de refresco
- **Formato de Respuesta:** Envolvente JSON canónica con `status`, `message`, `data` y `errors`
- **Encabezados:** Inyección automática `X-App-Key` para límite de velocidad elevado (opcional)

Ver **[Guía de Compatibilidad API](./API-COMPATIBILITY.md)** para el contrato completo.

## 🏗️ Formato de Respuesta Estándar

El `ApiClient` normaliza todas las respuestas de la API a esta estructura:

```php
[
    'ok'          => bool,           // true para 2xx, false en otro caso
    'status'      => int,            // Código de estado HTTP
    'data'        => array,          // Payload principal
    'messages'    => array,          // Mensajes derivados del wrapper para UX del controlador
    'fieldErrors' => array,          // Errores derivados del wrapper para UX del controlador
    'raw'         => string,         // Cuerpo JSON original
]
```

## ✅ Validación de Formularios y Capa de Solicitud

Toda la validación de formularios se maneja a través de clases en `app/Modules/{ModuleName}/Requests/*Request.php`:

- **rules():** Reglas de validación a nivel UI (`required`, `valid_email`, `max_length`)
- **payload():** Normalización al formato esperado por la API
- **validate():** Recopilación automática de errores y mapeo de campos
- **Sin validación de base de datos:** La validación de lógica de negocio pertenece al backend

Ejemplo de uso en controlador:
```php
$request = service('formRequest', UserCreateRequest::class, false);
$invalid = $this->validateRequest($request);
if ($invalid !== null) return $invalid;

$response = $this->safeApiCall(
    fn() => $this->userService->create($request->payload())
);
```

Ver **[Guía de Capa de Validación](./VALIDATION-LAYER.md)** para patrones detallados.

## 🛠️ Requisitos

- **PHP** 8.1 o superior
- **Composer** 2.x
- **Node.js** 16+ (para compilaciones de Tailwind CSS)
- **Extensiones PHP:**
  - `intl` (requerida)
  - `mbstring` (requerida)
  - `curl` (recomendada)
  - `json` (recomendada)

## 📦 Instalación

### Opción 1: Configuración Automatizada (Recomendado)

```bash
bash install.sh
```

Este script maneja:
- Creación y configuración de archivo de entorno
- Dependencias de Composer
- Dependencias de npm
- Reemplazo de variables de plantilla (nombre de app, URL de API, etc)

### Opción 2: Configuración Manual

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias npm
npm install

# Copiar plantilla de entorno
cp env .env
```

Editar `.env` con tu configuración:

```dotenv
# Aplicación
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8182/'

# API Backend
apiClient.baseUrl = 'http://localhost:8180'
apiClient.apiPrefix = '/api/v1'

# Opcional: Google OAuth (para botón "Iniciar sesión con Google")
GOOGLE_CLIENT_ID = 'tu-client-id.apps.googleusercontent.com'

# Opcional: Límite de carga de archivos
FILE_MAX_SIZE = 10485760

# Opcional: Clave API de aplicación para límite de velocidad elevado
# Crear vía /admin/api-keys o POST /api/v1/api-keys en el backend
# apiClient.appKey = apk_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Ver **[Inicio Rápido](./QUICK-START.md)** para instrucciones detalladas de configuración.

## 🚀 Desarrollo

Iniciar ambos servidores en ventanas de terminal separadas:

**Terminal 1 — Servidor de Desarrollo PHP:**
```bash
php spark serve --port 8182
# Aplicación disponible en http://localhost:8182
```

**Terminal 2 — Observador de CSS de Tailwind:**
```bash
npm run dev:css
# Recompila CSS en cambios de archivo
```

Ambos deben ejecutarse durante el desarrollo. En producción, CSS está precompilado.

## ✔️ Calidad y Pruebas

```bash
# Ejecutar todas las pruebas
composer test

# Ejecutar suites de prueba específicas
composer test:unit
composer test:feature

# Análisis estático (PHPStan)
composer analyse

# Verificación de estilo de código
composer format:check

# Corregir estilo de código automáticamente
composer format

# Verificación de calidad completa (pruebas + análisis + estilo)
composer quality
```

## 📁 Estructura del Proyecto

```
app/
├── Modules/                    # Módulos de características (Auth, Users, Files, etc)
│   ├── Auth/
│   │   ├── Config/Routes.php
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   ├── Services/
│   │   ├── Views/
│   │   └── Language/
│   ├── Users/
│   ├── Files/
│   ├── Dashboard/
│   ├── Profile/
│   ├── Audit/
│   ├── ApiKeys/
│   ├── Metrics/
│   └── Language/
│
├── Filters/                    # Filtros Auth, Admin, Locale
├── Libraries/                  # ApiClient y librerías personalizadas
├── Helpers/                    # Ayudantes UI y de formularios
├── Config/                     # Configuración de framework y aplicación
├── Language/                   # Archivos i18n globales (fallback)
├── Models/                     # (No utilizado — todos los datos vienen de la API)
└── Traits/                     # Traits compartidos

docs/
├── INDEX.md             # Hub de documentación
├── QUICK-START.md       # Guía de configuración
├── ARCHITECTURE.md      # Diseño del sistema y ApiClient
├── SERVICES.md          # Patrón de servicios y validación
├── FRONTEND.md          # Componentes UI y patrones
├── TESTING.md           # Estrategias de pruebas
├── DEPLOYMENT.md        # Checklist de producción
├── TROUBLESHOOTING.md   # Problemas comunes
└── FAQ.md              # Preguntas frecuentes

tests/
├── unit/                # Pruebas unitarias (librerías, filtros, servicios)
├── feature/             # Pruebas de características (flujos de controlador)
└── README.md            # Documentación de pruebas
```

## 🛡️ Seguridad

- Tokens JWT almacenados **solo en sesiones PHP del lado del servidor**, nunca en localStorage o cookies
- Protección CSRF habilitada por defecto
- Encabezados de Política de Seguridad de Contenido (CSP) configurables
- Descargas de archivos validadas en tamaño antes del envío a la API
- Clave API almacenada en `.env`, nunca expuesta al código del lado del cliente
- Nunca hacer commit de archivos `.env` o hardcodear secretos

**Checklist de Seguridad de Producción:**
- ✅ Establecer `CI_ENVIRONMENT = production`
- ✅ Establecer `app.forceGlobalSecureRequests = true`
- ✅ Habilitar `app.CSPEnabled = true`
- ✅ Establecer `cookie.secure = true`
- ✅ Verificar que `app.baseURL` usa HTTPS
- ✅ Ejecutar `composer install --no-dev --optimize-autoloader`
- ✅ Construir CSS: `npm ci && npm run build:css`
- ✅ Asegurar que `public/` es DocumentRoot
- ✅ Establecer permisos correctos en `writable/`

Ver **[Guía de Despliegue](./DEPLOYMENT.md)** para checklist completo.

## 🎯 Uso de Plantilla

Para crear un nuevo proyecto a partir de esta plantilla:

1. **Marca:** Actualizar nombre de app y colores en `head.php`
2. **Configuración API:** Establecer `apiClient.baseUrl` en `.env`
3. **Módulos:** Eliminar módulos no utilizados (Audit, ApiKeys, Metrics, Files) de rutas y barra lateral
4. **Localización:** Mantener solo locales necesarios (English/Español)
5. **Puertas de Calidad:** Ejecutar `composer quality` para asegurar estándares
6. **Git Hooks:** Ejecutar `npm run prepare` para instalar hooks de git

## 🔗 Recursos Externos

- **[Documentación de CodeIgniter 4](https://codeigniter.com/user_guide/)** — Referencia del framework
- **[ci4-api-starter](https://github.com/dcardenasl/ci4-api-starter)** — Plantilla de API Backend
- **[Documentación de Tailwind CSS](https://tailwindcss.com/)** — Framework CSS basado en utilidades
- **[Documentación de Alpine.js](https://alpinejs.dev/)** — Framework JavaScript ligero
- **[Iconos Lucide](https://lucide.dev/)** — Librería de iconos

## 📝 Licencia

Este proyecto es de código abierto. Ver archivo LICENSE para detalles.

## 🤝 Contribuyendo

¡Las contribuciones son bienvenidas! Por favor ver [CONTRIBUTING.md](../CONTRIBUTING.md) para directrices.

## ❓ ¿Necesitas Ayuda?

- **¿Primera vez?** Comienza con **[INICIO RÁPIDO](./QUICK-START.md)**
- **¿Algo roto?** Verifica **[RESOLUCIÓN DE PROBLEMAS](./TROUBLESHOOTING.md)**
- **¿Tienes una pregunta?** Ver **[PREGUNTAS FRECUENTES](./FAQ.md)**
- **¿Quieres entender el sistema?** Lee **[ARQUITECTURA](./ARCHITECTURE.md)**

---

**Última Actualización:** 2026-04-15  
**Estado:** Listo para Producción ✅
