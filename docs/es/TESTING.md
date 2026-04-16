# Pruebas y Calidad

Este documento describe nuestra estrategia para asegurar la estabilidad y confiabilidad de **CI4 Admin Starter**.

## 🧪 Estrategia de Pruebas

Dado que este proyecto es un frontend que consume una API, nuestras pruebas se enfocan en dos niveles:

1. **Pruebas Unitarias:** Pruebas de componentes individuales (como normalización de `FormRequest` o lógica de `ApiClient`) aisladamente.
2. **Pruebas de Características:** Pruebas de acciones completas de controlador (p.ej., envío de formulario de login) mockando respuestas de API.

---

## 🛠️ Ejecutar Pruebas

Usa los siguientes comandos desde la raíz del proyecto:

```bash
# Ejecutar todas las pruebas
vendor/bin/phpunit

# Ejecutar solo pruebas unitarias
vendor/bin/phpunit tests/unit/

# Ejecutar solo pruebas de características
vendor/bin/phpunit tests/feature/

# Ejecutar con cobertura (requiere Xdebug)
vendor/bin/phpunit --coverage-text
```

---

## 🎭 Mockear la API

Para probar controladores sin un backend ejecutándose, hacemos mock de **Servicios** en lugar del `ApiClient`.

### Ejemplo: Mockear Flujo de Login
En `tests/feature/AuthFlowTest.php`, creamos un mock de `AuthApiService` e inyectamos en el contenedor de Servicios de CodeIgniter:

```php
public function testLoginSuccess()
{
    // 1. Crear el mock
    $authService = $this->createMock(AuthApiService::class);
    
    // 2. Definir la respuesta esperada de la API
    $authService->method('login')->willReturn([
        'ok'     => true,
        'status' => 200,
        'data'   => [
            'access_token'  => 'fake-token',
            'refresh_token' => 'fake-refresh',
            'user'          => ['role' => 'admin']
        ]
    ]);

    // 3. Inyectar el mock
    Services::injectMock('authApiService', $authService);

    // 4. Realizar la solicitud
    $result = $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'password'
    ]);

    // 5. Afirmar resultados
    $result->assertRedirectTo('/dashboard');
    $result->assertSessionHas('access_token');
}
```

---

## 📐 Probar `FormRequest`

La lógica de validación debe ser probada en `tests/unit/Requests/`. Enfócate en:
- Asegurar que `rules()` correctamente bloquee datos inválidos.
- Asegurar que `payload()` correctamente transforme entrada de formulario en contrato API `snake_case`.

---

## 💎 Herramientas de Calidad

Usamos varias herramientas para mantener estándares de código altos:

### 1. PHPStan (Análisis Estático)
Verifica errores de tipo y posibles bugs sin ejecutar el código.
```bash
vendor/bin/phpstan analyse
```

### 2. PHP-CS-Fixer (Estilo de Código)
Asegura que la base de código sigue estándares PSR-12 y CI4.
```bash
# Verificar problemas
vendor/bin/php-cs-fixer fix --dry-run --diff

# Corregir problemas automáticamente
vendor/bin/php-cs-fixer fix
```

### 3. ESLint (Estilo JavaScript)
Verifica los archivos JavaScript en `public/assets/js/`.
```bash
npm run lint:js
```

---

## ✅ Checklist para Nuevas Pruebas

- [ ] ¿Cubre la prueba el "Happy Path" (éxito)?
- [ ] ¿Cubre la prueba escenarios comunes de error (fallo de validación, API 401, API 500)?
- [ ] ¿Se reinician correctamente todos los mocks en `tearDown()`?
- [ ] Si agregas un nuevo Servicio, ¿agregaste una Interfaz y registraste en `Config/Services.php` para hacerlo mockeable?
