# Compatibilidad API: CI4 Admin Starter

## Objetivo

Definir reglas obligatorias para garantizar compatibilidad total entre este frontend (`ci4-admin-starter`) y el backend (`ci4-api-starter`).

## Principio de arquitectura

- Este proyecto es un **frontend administrativo template**.
- La base de datos y reglas de negocio pertenecen al backend.
- **Contrato Estricto:** El API espera y devuelve datos en **`snake_case`**. El frontend debe respetar este estándar en todos sus payloads JSON.

## Reglas de compatibilidad obligatorias

1. **Autenticación:** JWT en sesión server-side (`access_token`, `refresh_token`). El `ApiClient` maneja el refresco automático.
2. **Standard de Nombres:** Usar siempre `snake_case` para llaves JSON (ej: `first_name`, `original_name`).
3. **Flujo de Usuario:** La creación de usuarios por administrador dispara una **invitación obligatoria**. El frontend no debe intentar establecer contraseñas ni ofrecer un toggle para saltarse la invitación.
4. **Respuestas:** El `ApiClient` normaliza todas las respuestas (éxito y error) para que el frontend no tenga que lidiar con variaciones del backend.

## Contrato de Tablas Server-Driven

- Query params soportados: `search`, `filter[...]`, `sort`, `limit`, `page`, `cursor`.
- `cursor` tiene prioridad sobre `page` cuando ambos existen.
- `sort` se reenvia intacto al backend, incluyendo prefijo `-` para descendente.
- El template no debe traducir `sort` a `order_by/order_dir` ni `limit` a `per_page` en el contrato publico.

## Compatibilidad de Archivos (Upload/Download)

### Subida de archivos (Base64)
Para maximizar la fiabilidad, el frontend convierte los archivos a Base64 y los envía mediante un `POST` JSON estándar:
- Campo: `file` (Data URI Base64).
- Campo: `filename` (Nombre original).
- Límite de tamaño: `FILE_MAX_SIZE` (bytes), aplicado con límite efectivo `min(FILE_MAX_SIZE, upload_max_filesize, post_max_size)` en Admin.

### Descarga y Previsualización
- El controlador del Admin debe devolver la respuesta binaria con cabeceras correctas o redireccionar a una URL firmada del backend, sin modificar el payload binario.
- Esto es crítico para evitar que middleware o toolbars de desarrollo corrompan la respuesta.

## Normalización de Errores

El API devuelve errores en `snake_case`. El Admin debe usar `name`/`id` de formularios en `snake_case` para que la asociación de errores sea directa, sin mapeos de compatibilidad.

## Criterios de Aceptación para Cambios

Cualquier cambio arquitectónico (ej: cambiar de Multipart a Base64 o viceversa) debe ser documentado en este contrato y verificado en ambos proyectos simultáneamente para evitar regresiones.
