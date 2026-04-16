# Servicios y Validación

Este documento explica cómo **CI4 Admin Starter** se comunica con la API del backend y cómo garantizamos la integridad de los datos antes de enviar solicitudes.

## 🔌 Patrón de Servicios

Para mantener controladores delgados y enfocados en orquestación de UI, toda comunicación con la API se encapsula en **Servicios** ubicados en **`app/Modules/{ModuleName}/Services/`**.

Ubicaciones de ejemplo:
- `app/Modules/Users/Services/UserApiService.php`
- `app/Modules/Files/Services/FileApiService.php`
- `app/Modules/Auth/Services/AuthApiService.php`

### Características Principales:
- **Interfaces:** Cada servicio debe tener una Interfaz correspondiente (p.ej., `UserApiServiceInterface.php`). Esto permite mocking más fácil durante pruebas.
- **Clase Base:** La mayoría de servicios extienden `BaseApiService`, que proporciona `apiClient`.
- **Registro:** Los servicios se registran en `app/Config/Services.php` como instancias compartidas (via `service('usersApi')`).

### Llamada a Servicio de Ejemplo en un Controlador:
```php
// 1. Obtener el servicio
$userService = service('usersApi');

// 2. Realizar la llamada (envuelta para seguridad)
$response = $this->safeApiCall(fn() => $userService->findById($id));

// 3. Extraer datos o manejar fallo
if (!$response['ok']) {
    return $this->failApi($response, 'Usuario no encontrado');
}
$user = $this->extractData($response);
```

---

## ✅ Capa de Validación (`FormRequest`)

Usamos una capa de validación dedicada ubicada en **`app/Modules/{ModuleName}/Requests/`** para separar la validación de UI/Formulario de la lógica de negocio.

Ubicaciones de ejemplo:
- `app/Modules/Users/Requests/UserStoreRequest.php`
- `app/Modules/Files/Requests/FileUploadRequest.php`
- `app/Modules/Auth/Requests/LoginRequest.php`

### 1. `rules()`
Define las reglas de validación de CodeIgniter 4. Esto debe enfocarse en **restricciones de sintaxis y UI** (p.ej., `required`, `valid_email`, `max_length`).
- Evita reglas que requieran acceso a base de datos (p.ej., `is_unique`). Estas pertenecen a la API Backend.

### 2. `payload()`
Este es el método más crítico. **Normaliza** los datos del formulario a la estructura exacta y nombrado en `snake_case` esperado por la API.
- Convierte tipos (p.ej., string a int/bool).
- Recorta espacios en blanco.
- Elimina campos opcionales que están vacíos.

### 3. Uso en Controlador
```php
// Resolver la solicitud (usar false para obtener una instancia fresca)
$request = service('formRequest', UserStoreRequest::class, false);

// Validar y redirigir atrás con errores si falla
$invalid = $this->validateRequest($request);
if ($invalid !== null) {
    return $invalid;
}

// Obtener el payload limpio y normalizado
$payload = $request->payload();

// Enviar a la API
$response = $this->safeApiCall(fn() => $this->userService->create($payload));
```

---

## 🛠️ Ayudantes de Manejo de Errores

El `BaseWebController` proporciona varios ayudantes para manejar respuestas de API con elegancia:

### `safeApiCall(callable $callback)`
Envuelve una llamada API en un bloque `try/catch`. Si la conexión falla o ocurre una excepción, devuelve una respuesta sintética "ok: false" en lugar de crashear la app.

### `failApi(array $response, string $fallbackMessage)`
Maneja una respuesta de API fallida mediante:
1. Extrayendo `fieldErrors` y redirigiendo al formulario con errores destacados.
2. Si no hay errores de campo, usa el `message` de la API (o `fallbackMessage`) para mostrar flash error.

### `extractData(array $response)` / `extractItems(array $response)`
Ayudantes para extraer los datos reales de la envolvente de respuesta estándar de la API, manejando correctamente claves `data` anidadas en resultados paginados.

---

## 🔄 Sincronización de Datos

- **Locales:** El `ApiClient` automáticamente envía el encabezado `Accept-Language`.
- **Errores:** Las claves de error de la API (p.ej., `email_already_registered`) pueden mapearse a traducciones locales en `BaseWebController::localizeApiMessage()`.
- **Snake Case:** Siempre usa `snake_case` para nombres de campos en tus reglas `FormRequest` y payloads para coincidir con el contrato Backend.
