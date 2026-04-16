# Flujos Críticos del Admin

Este documento detalla implementaciones específicas que garantizan la estabilidad del Admin al comunicarse con la API. **No cambies estas lógicas sin entender su impacto.**

## 1. Carga de Archivos (Modo Base64)

Para garantizar compatibilidad máxima y evitar errores de cURL o límites de protocolos multipart, el Admin utiliza **Base64** como método primario de carga.

- **Ubicación:** `App\Services\FileApiService::upload()`
- **Lógica:** El archivo se lee del disco, se codifica a Base64 y se envía en un payload JSON mediante POST estándar.
- **Ventaja:** Es inmune a problemas de "boundary" de multipart y permite al API procesar el archivo resilientemente.

## 2. Reintentos del ApiClient (Rewind)

El `ApiClient` tiene lógica de auto-refresco de tokens JWT. Si una solicitud falla con `401`, intenta refrescar el token y reenviar la solicitud original.

- **⚠️ Punto Crítico:** Si la solicitud original contenía streams, estos se consumen en el primer intento.
- **Solución:** En `ApiClient::request()`, antes del reintento, se aplica `rewind($stream)` para asegurar que el segundo intento no envíe un cuerpo vacío.

## 3. Visualización de Imágenes y Descargas

Las imágenes y descargas pasan por un proxy en el Admin (`FileController::view` y `FileController::download`) para inyectar encabezados de autenticación de la API.

- **⚠️ Problema de la Barra de Depuración:** CodeIgniter intenta inyectar código HTML de "Debug Toolbar" en todas las respuestas. Si la respuesta es binaria, se corrompe.
- **Solución:** El controlador debe devolver la respuesta binaria con `Content-Type` y `Content-Disposition` correctos, o redirigir a la URL del backend.

## 4. Normalización de Errores de Validación

La API y el Admin usan el mismo estándar `snake_case` para llaves de validación.

- **Lógica:** No se mantiene capa de compatibilidad `camelCase`.
- **Impacto:** Si se añade nuevo campo al API, debe usarse el mismo nombre `snake_case` en formularios del Admin.
