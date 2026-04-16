# Compatibilidad API: CI4 Admin Starter

## Objetivo

Definir reglas obligatorias para garantizar compatibilidad total entre este frontend (`ci4-admin-starter`) y el backend (`ci4-api-starter`).

## Principio de Arquitectura

- Este proyecto es una **plantilla frontend administrativo**.
- La base de datos y reglas de negocio pertenecen al backend.
- **Contrato Estricto:** La API espera y devuelve datos en **`snake_case`**. El frontend debe respetar este estándar en todos sus payloads JSON.

## Reglas de Compatibilidad Obligatorias

1. **Autenticación:** JWT en sesión del lado del servidor (`access_token`, `refresh_token`). El `ApiClient` maneja el refresco automático.
2. **Estándar de Nombres:** Usar siempre `snake_case` para llaves JSON (p.ej., `first_name`, `original_name`).
3. **Flujo de Usuario:** La creación de usuarios por administrador dispara una **invitación obligatoria**. El frontend no debe intentar establecer contraseñas ni ofrecer toggle para saltarse la invitación.
4. **Respuestas:** El `ApiClient` normaliza todas las respuestas (éxito y error) para que el frontend no tenga que lidiar con variaciones del backend.

## Contrato de Tablas Impulsadas por Servidor

- Query params soportados: `search`, `filter[...]`, `sort`, `limit`, `page`, `cursor`.
- `cursor` tiene prioridad sobre `page` cuando ambos existen.
- `sort` se reenvía intacto al backend, incluyendo prefijo `-` para descendente.
- La plantilla no debe traducir `sort` a `order_by/order_dir` ni `limit` a `per_page`.

## Compatibilidad de Archivos (Carga/Descarga)

### Carga de Archivos (Base64)
Para maximizar confiabilidad, el frontend convierte archivos a Base64 y los envía mediante POST JSON estándar:
- Campo: `file` (Data URI Base64).
- Campo: `filename` (Nombre original).
- Límite de tamaño: `FILE_MAX_SIZE` (bytes), aplicado con límite efectivo `min(FILE_MAX_SIZE, upload_max_filesize, post_max_size)`.

### Descarga y Previsualización
- El controlador del Admin debe devolver la respuesta binaria con encabezados correctos o redirigir a una URL firmada del backend.
- Esto es crítico para evitar que middleware o toolbars de desarrollo corrompan la respuesta.

## Normalización de Errores

La API devuelve errores en `snake_case`. El Admin debe usar `name`/`id` de formularios en `snake_case` para que la asociación de errores sea directa.

## Criterios de Aceptación para Cambios

Cualquier cambio arquitectónico debe ser documentado en este contrato y verificado en ambos proyectos simultáneamente.
