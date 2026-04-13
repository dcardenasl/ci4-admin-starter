# Flujos Críticos del Admin

Este documento detalla implementaciones específicas que garantizan la estabilidad del Admin al comunicarse con el API. **No cambies estas lógicas sin entender su impacto.**

## 1. Subida de Archivos (Modo Base64)

Para garantizar la máxima compatibilidad y evitar errores de cURL o límites de protocolos multipart, el Admin utiliza **Base64** como método primario de subida.

- **Ubicación:** `App\Services\FileApiService::upload()`
- **Lógica:** El archivo se lee del disco, se codifica a Base64 y se envía en un payload JSON mediante una petición `POST` estándar.
- **Ventaja:** Es inmune a problemas de "boundary" de multipart y permite al API procesar el archivo de forma resiliente.

## 2. Re-intentos del ApiClient (Rewind)

El `ApiClient` tiene una lógica de auto-refresco de tokens JWT. Si una petición falla con un `401`, intenta refrescar el token y re-enviar la petición original.

- **⚠️ Punto Crítico:** Si la petición original contenía recursos (streams), estos se consumen en el primer intento.
- **Solución:** En `ApiClient::request()`, antes del re-intento, se recorre el array `multipart` y se aplica `rewind($stream)` para asegurar que el segundo intento no envíe un cuerpo vacío.

## 3. Visualización de Imágenes y Descargas

Las imágenes y descargas pasan por un proxy en el Admin (`FileController::view` y `FileController::download`) para inyectar las cabeceras de autenticación del API.

- **⚠️ El problema de la Barra de Depuración:** CodeIgniter intenta inyectar el código HTML de la "Debug Toolbar" en todas las respuestas. Si la respuesta es una imagen binaria, esto corrompe el archivo y lanza un `TypeError`.
- **Solución:** El controlador debe devolver la respuesta binaria con `Content-Type` y `Content-Disposition` correctos, o redirigir a la URL de descarga del backend, evitando cualquier mutación del cuerpo binario.

## 4. Normalización de Errores de Validación

El API y el Admin usan el mismo estándar `snake_case` para llaves de validación.

- **Lógica:** No se mantiene capa de compatibilidad `camelCase`.
- **Impacto:** Si se añade un nuevo campo al API, debe usarse el mismo nombre `snake_case` en los formularios del Admin para conservar el mapeo directo de errores.
