# Preguntas Frecuentes (FAQ)

Preguntas comunes sobre desarrollo y despliegue de **CI4 Admin Starter**.

## Preguntas Generales

### ¿Qué es CI4 Admin Starter?

**CI4 Admin Starter** es una plantilla de panel administrativo lista para producción construida con CodeIgniter 4. Está diseñada para consumir una API backend (`ci4-api-starter`) y proporcionar una interfaz renderizada en servidor para administrar datos, usuarios, archivos y más.

Características clave:
- Vistas renderizadas en servidor (PHP + Tailwind CSS)
- Autenticación JWT con refresco automático de token
- Capa de servicios para comunicación limpia con la API
- Patrón de validación FormRequest
- Módulos integrados (Auth, Dashboard, Users, Files, Audit, Metrics, API Keys)
- Soporte i18n (Inglés & Español)
- Suite de pruebas integral

### ¿Es una SPA (Single Page Application)?

No. Este es un **Frontend Renderizado en Servidor (SRF)**. Cada solicitud va al servidor PHP, que maneja enrutamiento, validación y renderizado de vistas. El frontend y backend son aplicaciones separadas que se comunican vía HTTP/REST API.

### ¿Puedo usar esto para una aplicación no-admin?

Sí, pero está optimizado para interfaces admin. Los patrones de diseño y módulos incluidos (Usuarios, Audit Logs, API Keys) son típicos de paneles administrativos.

Para aplicaciones generales, podrías:
- Remover módulos innecesarios
- Personalizar la UI
- Agregar tus propios módulos de dominio

### ¿Es apto para producción?

Sí. El proyecto incluye:
- ✅ Cobertura completa de pruebas (unit + feature)
- ✅ Mejores prácticas de seguridad (CSRF, CSP, almacenamiento seguro)
- ✅ Manejo de errores y logging
- ✅ Optimizaciones de rendimiento
- ✅ Checklist de despliegue en [DEPLOYMENT.md](./DEPLOYMENT.md)

---

## Arquitectura y Diseño

### ¿Por qué server-rendered y no SPA?

Las páginas renderizadas en servidor son más simples de construir y mantener. Reducen la complejidad de herramientas frontend y gestión de estado. Para interfaces admin, la diferencia de rendimiento es negligible.

Una SPA requeriría:
- Setup de bundling complejo (Webpack, Vite)
- Gestión de estado del cliente (Redux, Zustand)
- Manejo de errores complejo
- Consideraciones de despliegue frontend

Server-rendered mantiene la arquitectura directa.

### ¿Cómo funciona la autenticación?

1. **Usuario inicia sesión** → API Backend valida credenciales y devuelve tokens JWT
2. **Frontend almacena tokens** en sesión PHP del lado del servidor (nunca en localStorage/cookies)
3. **En llamadas API** → Token se lee de sesión y se envía en encabezado `Authorization: Bearer <token>`
4. **Si el token expira** → ApiClient automáticamente llama endpoint de refresco y reintenta
5. **Al cerrar sesión** → Sesión se destruye, usuario redirigido a login

Este enfoque es más seguro que almacenar tokens en el navegador.

### ¿Qué es el patrón FormRequest?

FormRequest es una capa de validación que centraliza la validación de formularios en clases dedicadas dentro de cada módulo (`app/Modules/{ModuleName}/Requests/*Request.php`).

Beneficios:
- Los controladores permanecen delgados y enfocados
- Las reglas de validación son reutilizables
- Formateo automático de errores
- Normalización de payload (conversión de tipos, mapeo de campos)

---

## API y Comunicación Backend

### ¿Cómo comunico con la API?

A través de **Services** en `app/Modules/{ModuleName}/Services/`:

1. El Controlador obtiene el Servicio: `service('usersApi')`
2. El Servicio usa `ApiClient` para llamadas HTTP
3. El `ApiClient` maneja autenticación, refresco de tokens y normalización de respuestas
4. El Controlador renderiza vistas con los datos

Ver [SERVICES.md](./SERVICES.md) para detalles.

### ¿Debo validar en el frontend o en el backend?

**Ambos:**
- **Frontend:** Valida sintaxis, formato, constraints UI (`required`, `valid_email`, `max_length`)
- **Backend:** Valida lógica de negocio (unicidad, permisos, invariantes)

Las validaciones frontend son para UX. Las del backend protegen la integridad de datos.

### ¿Cómo manejo errores de la API?

El `ApiClient` normaliza TODAS las respuestas a:
```php
[
    'ok' => true/false,
    'status' => 200/422/500,
    'data' => [...],
    'fieldErrors' => ['email' => 'Ya existe'],
    'messages' => ['Error message']
]
```

Los Controladores usan `failApi()` para manejar errores graciosamente.

---

## Seguridad

### ¿Dónde se almacenan los tokens JWT?

En **sesión PHP del lado del servidor**. Nunca en `localStorage` o `cookies` (excepto `session_id`). Esto previene vulnerabilidades XSS.

### ¿Está protegido contra CSRF?

Sí. CSRF está habilitado por defecto en CodeIgniter 4. Cada formulario incluye token CSRF vía `{{ csrf_field() }}`.

Ver [Deployment Guide](./DEPLOYMENT.md) para checklist de seguridad completo.

---

## Despliegue

### ¿Cómo despliego a producción?

1. Clona el repositorio en el servidor
2. Ejecuta `composer install --no-dev`
3. Configura `.env` (HTTPS, URLs, credenciales)
4. Ejecuta `npm ci && npm run build:css`
5. Configura servidor web (Nginx/Apache) pointing a `public/`
6. Ejecuta checklist de [DEPLOYMENT.md](./DEPLOYMENT.md)

### ¿Debo usar Docker?

No es obligatorio. Pero es recomendado para consistencia entre desarrollo y producción.

Ver [DEPLOYMENT.md](./DEPLOYMENT.md) para ejemplos de configuración de servidor.

---

## Soporte y Contacto

¿Tienes preguntas que no están aquí?
1. Revisa [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) para problemas comunes
2. Revisa [ARCHITECTURE.md](./ARCHITECTURE.md) para entender el sistema
3. Abre un issue en [GitHub](https://github.com/dcardenasl/ci4-admin-starter/issues)
