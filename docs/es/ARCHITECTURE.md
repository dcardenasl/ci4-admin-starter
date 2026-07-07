# Arquitectura y Conceptos Clave

Este documento explica los fundamentos técnicos de **CI4 Admin Starter** y cómo interactúa con la API del backend.

## 🏛️ Descripción General de Arquitectura

Este proyecto es un **Frontend Renderizado en Servidor (SRF)**. A diferencia de una aplicación SPA (Single Page Application) tradicional, usa CodeIgniter 4 para manejar enrutamiento, gestión de sesiones y renderizado de vistas, pero **nunca accede a una base de datos directamente**.

### Diagrama de Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      Navegador del Usuario Final                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Páginas HTML  │  Tailwind CSS  │  Alpine.js  │  Iconos Lucide  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │ Solicitud/Respuesta HTTP (+ Cookie de Sesión)
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                  CI4 Admin Starter (Puerto 8082)                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────┐  │
│  │   Rutas      │  │ Controladores│  │  Servicios   │  │ ApiClient  │  │
│  │              │→ │              │→ │              │→ │            │  │
│  │ /users/data  │  │ UserController│ │UserApiService│ │Cliente HTTP│  │
│  └──────────────┘  └──────────────┘  └──────────────┘  └────────────┘  │
│         │                │                    │                │         │
│         │                ▼                    │                │         │
│         │           ┌──────────────┐          │                │         │
│         │           │ FormRequest  │          │                │         │
│         │           │              │          │                │         │
│         │           │ • rules()    │          │                │         │
│         │           │ • validate() │          │                │         │
│         │           │ • payload()  │          │                │         │
│         │           └──────────────┘          │                │         │
│         │                                     │                │         │
│         └──────────────────────────────────────┼────────────────┘         │
│             Almacenamiento de Sesión PHP      │                         │
│          (access_token, user, locale)         │                         │
│                                               ▼                         │
│                      ┌────────────────────────────────┐                 │
│                      │ Renderizado de Vistas (PHP)    │                 │
│                      │                                │                 │
│                      │ • Layout con Barra Lateral     │                 │
│                      │ • Datos pasados a plantilla    │                 │
│                      │ • Respuesta HTML               │                 │
│                      └────────────────────────────────┘                 │
└─────────────────────────────────┬──────────────────────────────────────┘
                                 │ Respuesta HTTP (HTML)
                                 │
                                 │ Token JWT Enviado vía Encabezado
                                 │ Authorization: Bearer <token>
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│            CI4 API Starter (Backend) (Puerto 8080)                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────┐  │
│  │   Rutas      │  │ Controladores│  │  Servicios   │  │ Middleware │  │
│  │              │→ │              │→ │              │→ │            │  │
│  │/api/v1/users │  │ UserController│ │ UserService  │  │ Verificación│  │
│  └──────────────┘  └──────────────┘  └──────────────┘  └────────────┘  │
│         │                │                    │                │         │
│         │                ▼                    ▼                ▼         │
│         │           ┌───────────────────────────────────────┐            │
│         │           │  Capa de Base de Datos                │            │
│         │           │  • Modelos y Repositorios            │            │
│         │           │  • Lógica de Negocio                 │            │
│         │           │  • Persistencia de Datos             │            │
│         │           └───────────────────────────────────────┘            │
│         └──────────→                                                    │
└─────────────────────────────────┬──────────────────────────────────────┘
                                 │ Respuesta JSON
                                 │ {status, message, data, errors}
                                 ▼
                            ┌──────────────┐
                            │  Base de     │
                            │  Datos       │
                            │ (MySQL/etc)  │
                            └──────────────┘
```

### Flujo de Solicitud/Respuesta

1. **Usuario realiza solicitud** → Navegador envía solicitud HTTP a Admin (puerto 8082)
2. **Enrutador distribuye** → `app/Config/Routes.php` enruta al Controlador apropiado
3. **Validación** → Controlador instancia `FormRequest` y valida entrada
4. **Capa de servicios** → Controlador llama método de Servicio apropiado
5. **Comunicación API** → Servicio usa `ApiClient` para enviar solicitud HTTP a Backend (puerto 8080)
6. **Procesamiento backend** → API Backend valida, procesa lógica de negocio, consulta base de datos
7. **Normalización de respuesta** → `ApiClient` normaliza respuesta JSON a formato estándar
8. **Renderizado de vista** → Controlador renderiza plantilla PHP con datos de respuesta
9. **Respuesta HTML** → Admin envía página HTML completa de vuelta al navegador
10. **Visualización** → Navegador renderiza HTML con Tailwind CSS y Alpine.js para interactividad

---

## 🛰️ Análisis Profundo de `ApiClient`

El `ApiClient` (`app/Libraries/ApiClient.php`) es el corazón de la aplicación. Encapsula toda la complejidad relativa a comunicación HTTP.

### 1. Refresco Automático de Tokens

Cuando una solicitud falla con estado `401 Unauthorized`, el ApiClient refresca automáticamente el token:

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Realizar Solicitud API                                       │
│    GET /api/v1/users                                            │
│    Authorization: Bearer <access_token> (expirado)              │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. Backend devuelve 401 No Autorizado                           │
│    (access_token ha expirado)                                   │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. ApiClient Intercepta 401                                     │
│    Lee refresh_token de la sesión PHP                           │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Enviar Solicitud de Refresco                                 │
│    POST /api/v1/auth/refresh                                    │
│    {refresh_token: "..."}                                       │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. Backend Devuelve Nuevos Tokens                               │
│    {                                                             │
│      access_token: "eyJ...",                                    │
│      refresh_token: "...",                                      │
│      expires_in: 3600                                           │
│    }                                                             │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. Actualizar Sesión y Reintentar Solicitud Original            │
│    session('access_token', new_token)                           │
│    session('token_expires_at', now + 3600)                      │
│    GET /api/v1/users (reintentado con nuevo token)              │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7. Solicitud Original Tiene Éxito                               │
│    Devuelve 200 OK con datos de usuarios                        │
│    ¡Todo transparente para el controlador!                      │
└─────────────────────────────────────────────────────────────────┘
```

**Puntos Clave:**
- El refresco automático es **transparente** para controladores y servicios
- Token almacenado en **sesión PHP del lado del servidor**, nunca en navegador
- Si el refresco falla (token de refresco expirado), sesión se destruye y usuario redirigido a login
- Cada refresco actualiza tanto `access_token` como `refresh_token` por seguridad

### 2. Normalización de Respuesta
Cada llamada devuelve una estructura de array consistente:
- `ok` (bool): `true` para códigos de estado 2xx.
- `status` (int): Código de estado HTTP.
- `data` (array): El payload principal de la API.
- `messages` (array): Mensajes derivados del wrapper para UX del controlador.
- `fieldErrors` (array): Errores derivados del wrapper para UX del controlador.
- `raw` (string): El cuerpo JSON original.

### 3. Sincronización de Localización
El `ApiClient` inyecta automáticamente el encabezado `Accept-Language` basado en la localidad actual de la sesión del usuario (`en` o `es`), asegurando que el Backend devuelva mensajes en el idioma correcto.

---

## 🛡️ Patrones de Seguridad

### JWT Basado en Sesión
Mientras el Backend es sin estado (JWT), el Admin es **con estado** (Sesiones PHP).
- **Almacenamiento:** Los tokens JWT se almacenan en sesiones PHP del lado del servidor, nunca en `localStorage` o `cookies` del navegador (excepto por `session_id`). Esto mitiga riesgos de XSS.
- **Vida Útil:** El Admin rastrea el valor `expires_at` para manejar proactivamente la expiración de sesión.

### Política de Seguridad de Contenido (CSP)
El proyecto está diseñado para trabajar con encabezados CSP estrictos.
- **Nonces:** Todos los scripts e estilos inline (donde se usan) deben incluir un nonce CSP vía `csp_script_nonce()`.
- **Recursos Externos:** Tailwind y Alpine se cargan vía CDN, que deben estar en whitelist en `Config/ContentSecurityPolicy.php`.

### Redacción de Datos
Para prevenir filtrar información sensible en logs, el `ApiClient` incluye un método `redactData()` que automáticamente enmascara contraseñas, strings Base64 de archivos y payloads grandes antes de que lleguen al sistema `log_message()`.

---

## 📂 Flujo de Datos: Formulario a API

```
┌────────────────────────────────────────────────────────────────┐
│ 1. Usuario Envía Formulario (HTML POST)                         │
│    <form method="POST" action="/users">                          │
│      <input name="first_name" value="John"/>                     │
│      <input name="email" value="john@example.com"/>              │
│    </form>                                                       │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 2. Controlador Instancia FormRequest                            │
│    $request = service('formRequest',                            │
│        UserStoreRequest::class, false);                          │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 3. Validación (FormRequest::rules())                            │
│    Las reglas verifican:                                        │
│    • first_name es requerido                                   │
│    • email es formato válido                                   │
│    • email max 255 caracteres                                  │
│                                                                │
│    Si falla validación → redireccionar con errores de campo    │
└────────────────┬───────────────────────────────────────────────┘
                 │ (si es válido)
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 4. Normalización de Payload (FormRequest::payload())            │
│    Datos de Formulario de Entrada:                             │
│    [                                                            │
│      'first_name' => 'John',                                   │
│      'email' => 'john@example.com'                             │
│    ]                                                            │
│                                                                │
│    Salida (normalizado para API):                              │
│    [                                                            │
│      'first_name' => 'John',  // recortado                      │
│      'email' => 'john@example.com'  // minúsculas               │
│    ]                                                            │
│                                                                │
│    Tareas:                                                     │
│    • Recortar espacios en blanco                               │
│    • Convertir tipos (string a int, bool)                      │
│    • Filtrar campos vacíos                                     │
│    • Mapear nombres de campos a convenciones de API            │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 5. Llamada a Capa de Servicios                                  │
│    $response = $this->safeApiCall(                              │
│        fn() => $this->userService->create(                      │
│            $request->payload()                                  │
│        )                                                        │
│    );                                                           │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 6. ApiClient Realiza Solicitud HTTP                             │
│    POST /api/v1/users                                           │
│    Authorization: Bearer <access_token>                         │
│    Accept-Language: en                                          │
│    X-App-Key: <opcional>                                        │
│                                                                │
│    Cuerpo:                                                     │
│    {                                                            │
│      "first_name": "John",                                      │
│      "email": "john@example.com"                                │
│    }                                                            │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 7a. Éxito en Validación del Backend                             │
│     Respuesta: 201 Creado                                       │
│     {                                                            │
│       "ok": true,                                                │
│       "data": {id: 123, first_name: "John", email: "..."}       │
│     }                                                            │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 7b. Fallo en Validación del Backend                             │
│     Respuesta: 422 Entidad No Procesable                        │
│     {                                                            │
│       "ok": false,                                              │
│       "errors": {                                               │
│         "email": "El correo ya existe"                          │
│       }                                                         │
│     }                                                            │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 8. ApiClient Normaliza Respuesta                                │
│    Formato estándar (siempre):                                 │
│    {                                                            │
│      'ok' => true/false,                                        │
│      'status' => 201/422/500,                                   │
│      'data' => [...],                                           │
│      'messages' => [...],                                       │
│      'fieldErrors' => {email: 'El correo ya existe'}            │
│    }                                                            │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 9. Controlador Maneja Respuesta                                 │
│                                                                │
│    Si éxito ($response['ok']):                                 │
│      → Redireccionar con mensaje flash de éxito                │
│                                                                │
│    Si fallo (!$response['ok']):                                │
│      → Verificar fieldErrors                                    │
│      → Si errores de campo: Redireccionar con datos            │
│      → Si no: Mostrar mensaje de error general                 │
└────────────────┬───────────────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────────────┐
│ 10. Renderizado y Visualización de Vistas                       │
│     Controlador renderiza plantilla PHP con:                   │
│     • Formulario (si falló validación)                          │
│     • Valores de campos antiguos (repoblar)                     │
│     • Mensajes de error de campo                               │
│     • Mensajes flash de éxito/error                             │
└────────────────────────────────────────────────────────────────┘
```

**Conceptos Clave:**

- **Validación de FormRequest** sucede antes de la llamada API (validación frontend)
- **Validación del Backend** sucede en la API (validación de lógica de negocio)
- **Mapeo automático de errores** de respuesta API devuelta a campos de formulario
- **safeApiCall()** envuelve la llamada API para manejo de excepciones
- **Todas las respuestas normalizadas** a formato consistente por ApiClient
