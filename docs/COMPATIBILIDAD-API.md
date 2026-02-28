# Compatibilidad API: CI4 Admin Starter

## Objetivo

Definir reglas obligatorias para garantizar compatibilidad total entre este frontend (`ci4-admin-starter`) y el backend (`ci4-api-starter`).

## Principio de arquitectura

- Este proyecto es un **frontend administrativo template**.
- La base de datos y reglas de negocio pertenecen al backend.
- **Contrato Estricto:** El API espera y devuelve datos en **`camelCase`**. El frontend debe respetar este estándar en todos sus payloads JSON.

## Reglas de compatibilidad obligatorias

1. **Autenticación:** JWT en sesión server-side (`access_token`, `refresh_token`). El `ApiClient` maneja el refresco automático.
2. **Standard de Nombres:** Usar siempre `camelCase` para llaves JSON (ej: `firstName`, `originalName`).
3. **Flujo de Usuario:** La creación de usuarios por administrador dispara una **invitación obligatoria**. El frontend no debe intentar establecer contraseñas ni ofrecer un toggle para saltarse la invitación.
4. **Respuestas:** El `ApiClient` normaliza todas las respuestas (éxito y error) para que el frontend no tenga que lidiar con variaciones del backend.

## Compatibilidad de Archivos (Upload/Download)

### Subida de archivos (Base64)
Para maximizar la fiabilidad, el frontend convierte los archivos a Base64 y los envía mediante un `POST` JSON estándar:
- Campo: `file` (Data URI Base64).
- Campo: `filename` (Nombre original).

### Descarga y Previsualización
- El controlador del Admin **debe** usar `DownloadResponse` (`$this->response->download()`) para servir archivos binarios.
- Esto es crítico para evitar que la **Debug Toolbar** de CodeIgniter inyecte código HTML en la imagen y rompa el archivo.

## Normalización de Errores

El API suele devolver errores basados en nombres de columnas de DB (`snake_case`). El Admin mapea estos errores a `camelCase` mediante `BaseWebController::normalizeErrorKey()` para que coincidan con los IDs de los inputs del formulario.

## Criterios de Aceptación para Cambios

Cualquier cambio arquitectónico (ej: cambiar de Multipart a Base64 o viceversa) debe ser documentado en este contrato y verificado en ambos proyectos simultáneamente para evitar regresiones.
