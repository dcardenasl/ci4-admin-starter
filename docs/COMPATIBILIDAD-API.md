# Compatibilidad API: CI4 Admin Starter

## Objetivo

Definir reglas obligatorias para garantizar compatibilidad total entre este frontend (`ci4-admin-starter`) y el backend (`ci-api-tester` / contrato `ci4-api-starter`).

## Principio de arquitectura

- Este proyecto es un **frontend administrativo template**.
- La base de datos y reglas de negocio pertenecen al backend.
- El frontend no debe duplicar ni reinterpretar reglas de dominio.

## Backend esperado

- Implementacion operativa: `ci-api-tester`.
- Contrato de referencia de endpoints: `ci4-api-starter`.

Si ambos difieren, el frontend debe alinearse al contrato acordado antes de liberar cambios.

## Reglas de compatibilidad obligatorias

1. Mantener prefijo API `/api/v1` configurado en `app/Config/ApiClient.php`.
2. Mantener autenticacion por JWT en sesion server-side (`access_token`, `refresh_token`).
3. Mantener flujo de refresh token en 401 sin exponer tokens al navegador.
4. Respetar codigos HTTP del backend; no mapear errores a `200`.
5. Preservar nombres de campos JSON y estructuras de `data`, `message`, `messages`, `errors`.
6. Manejar respuestas no 2xx sin romper experiencia de usuario ni contrato tecnico.
7. En endpoints puente de tabla/datos, reenviar JSON del backend cuando aplique para evitar drift de contrato.

## Normalizacion de respuestas en frontend

`app/Libraries/ApiClient.php` entrega una estructura uniforme:

```php
[
    'ok'          => bool,   // status 2xx
    'status'      => int,    // HTTP status real
    'data'        => array,  // payload JSON decodificado
    'raw'         => string, // body crudo
    'messages'    => array,  // mensajes de negocio/validacion
    'fieldErrors' => array,  // errores por campo
]
```

Extraccion estandar:

- `messages`: `message` | `messages[]` | `errors.general`
- `fieldErrors`: `errors.<campo>` (excluye `general`)

## Contratos JSON que se deben soportar

### Exito simple

```json
{
  "message": "Operacion completada",
  "data": {
    "id": "123"
  }
}
```

### Validacion

```json
{
  "message": "Error de validacion",
  "errors": {
    "email": "El email es requerido",
    "password": "Minimo 8 caracteres",
    "general": "Datos invalidos"
  }
}
```

### Listado/paginacion

```json
{
  "data": [
    { "id": "1", "name": "Item" }
  ],
  "meta": {
    "page": 1,
    "limit": 25,
    "total": 120
  }
}
```

Tambien debe tolerar payload con `data` anidado (`{ "data": { "data": [...] } }`) por compatibilidad historica.

## Contrato de busqueda, filtros y paginacion

Para mantener compatibilidad con `ci4-api-starter`, los listados deben reenviar estos parametros:

- `search`: termino de busqueda.
- `filter`: objeto de filtros (`filter[status]=active`, `filter[role]=user`, etc.).
- `sort`: campo de orden, con `-` opcional para descendente (`sort=-created_at`).
- `limit`: tamano de pagina.
- `page`: paginacion por pagina.
- `cursor`: paginacion por cursor (si existe, tiene prioridad sobre `page`).

En respuestas de listado, el frontend debe tolerar y preservar:

- `data`: arreglo de items.
- `meta`: metadatos de paginacion (`page`, `limit`, `total`, u otros campos compatibles).
- Cualquier campo extra de navegacion (`links`, `next_cursor`, etc.) sin eliminarlo.

Regla de implementacion:

- Endpoints puente como `/files/data`, `/admin/users/data`, `/admin/audit/data` deben priorizar passthrough del JSON del backend para evitar divergencias de contrato.

## Guía para nuevas implementaciones

1. Agregar endpoint en servicio (`app/Services/*ApiService.php`).
2. Consumir desde controller via `safeApiCall()`.
3. Reutilizar `extractData()`, `extractItems()`, `firstMessage()` para consistencia.
4. No hardcodear estructuras ad-hoc por modulo si ya existe un patron comun.
5. Agregar/actualizar tests para codigos HTTP y parseo de JSON.

## Criterios de aceptacion para cambios de contrato

Un cambio se considera compatible solo si:

- No rompe parsing en `ApiClient`.
- No rompe `fieldErrors` ni mensajes mostrados en formularios.
- No altera semantica HTTP.
- Pasa pruebas existentes y nuevas para el caso modificado.

## Nota de versionado

Este contrato reemplaza aliases legacy de query y debe tratarse como cambio mayor coordinado entre frontend y backend.

## Referencias de codigo

- `app/Libraries/ApiClient.php`
- `app/Controllers/BaseWebController.php`
- `app/Config/ApiClient.php`
- `tests/unit/Libraries/ApiClientTest.php`
