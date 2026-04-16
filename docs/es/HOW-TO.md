# Guías y Cómo Hacer

Este documento proporciona instrucciones paso a paso para tareas comunes de desarrollo en **CI4 Admin Starter**.

---

## 🛠️ Cómo Agregar un Nuevo Módulo

Este proyecto usa una **arquitectura modular**. Para agregar una nueva característica (p.ej., "Productos"), sigue estos pasos:

### Paso 1: Verificación de Backend
Asegúrate de que los endpoints de la API correspondiente existan en el Backend (`ci4-api-starter`).

### Paso 2: Crear Estructura del Módulo

Crea la estructura de directorios bajo `app/Modules/Products/`:

```
app/Modules/Products/
├── Config/
│   └── Routes.php                 # Rutas del módulo (prefijo: products/)
├── Controllers/
│   └── ProductController.php      # Extiende BaseWebController
├── Language/
│   ├── en/
│   │   └── Products.php           # Traducciones en inglés
│   └── es/
│       └── Products.php           # Traducciones en español
├── Requests/                      # Solo si hay entradas de formulario
│   ├── ProductStoreRequest.php
│   └── ProductUpdateRequest.php
└── Services/
    ├── ProductApiService.php      # Extiende BaseApiService
    └── ProductApiServiceInterface.php  # (REQUERIDO) Interfaz para DI
```

También crea vistas en una ubicación separada:
```
app/Views/products/                 # Las vistas están fuera de módulos (ubicación compartida)
├── index.php                       # Vista de lista
├── show.php                        # Vista de detalle
├── create.php                      # Formulario de creación
├── edit.php                        # Formulario de edición
└── partials/
    ├── filters.php                 # Panel de filtros
    └── toolbar.php                 # Botones de acciones
```

### Paso 3: Crear Interfaz y Clase de Servicio

**`app/Modules/Products/Services/ProductApiServiceInterface.php`:**
```php
<?php
declare(strict_types=1);

namespace App\Modules\Products\Services;

interface ProductApiServiceInterface
{
    /** @return ApiResponse */
    public function list(array $filters = []): array;
    
    /** @return ApiResponse */
    public function get(int|string $id): array;
    
    /** @return ApiResponse */
    public function create(array $payload): array;
    
    /** @return ApiResponse */
    public function update(int|string $id, array $payload): array;
    
    /** @return ApiResponse */
    public function delete(int|string $id): array;
}
```

**`app/Modules/Products/Services/ProductApiService.php`:**
```php
<?php
declare(strict_types=1);

namespace App\Modules\Products\Services;

use App\Services\BaseApiService;

class ProductApiService extends BaseApiService implements ProductApiServiceInterface
{
    // Implementar métodos de interfaz...
}
```

### Paso 4: Registrar Servicio en `app/Config/Services.php`

```php
use App\Modules\Products\Services\ProductApiService;
use App\Modules\Products\Services\ProductApiServiceInterface;

public static function productApiService(bool $getShared = true): ProductApiServiceInterface
{
    if ($getShared) {
        /** @var ProductApiService */
        return static::getSharedInstance('productApiService');
    }
    return new ProductApiService(static::apiClient());
}
```

### Paso 5: Crear Controlador

**`app/Modules/Products/Controllers/ProductController.php`:**
```php
<?php
declare(strict_types=1);

namespace App\Modules\Products\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Products\Services\ProductApiServiceInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ProductController extends BaseWebController
{
    protected ProductApiServiceInterface $apiService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->apiService = service('productApiService');
    }

    public function index(): string
    {
        // Renderizar lista de productos...
    }
}
```

### Paso 6: Crear Archivos de Idioma

**`app/Modules/Products/Language/en/Products.php`:**
```php
<?php
return [
    'title'       => 'Products',
    'description' => 'Manage your products',
    // Agregar más traducciones...
];
```

### Paso 7: Crear Vistas

**`app/Views/products/index.php`:**
```php
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <!-- Vista de lista de productos -->
<?= $this->endSection() ?>
```

### Paso 8: Registrar Rutas

**`app/Modules/Products/Config/Routes.php`:**
```php
<?php
$routes->group('products', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'ProductController::index', ['as' => 'products.index']);
    $routes->get('(:num)', 'ProductController::show/$1', ['as' => 'products.show']);
    $routes->get('create', 'ProductController::create', ['as' => 'products.create']);
    $routes->post('/', 'ProductController::store', ['as' => 'products.store']);
    $routes->get('(:num)/edit', 'ProductController::edit/$1', ['as' => 'products.edit']);
    $routes->put('(:num)', 'ProductController::update/$1', ['as' => 'products.update']);
    $routes->delete('(:num)', 'ProductController::delete/$1', ['as' => 'products.delete']);
});
```

### Paso 9: Agregar a la Navegación

Edita `app/Views/layouts/partials/sidebar.php` y agrega:
```php
<a href="<?= site_url('products') ?>"
   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= active_nav('products*') ?>">
    <?= ui_icon('box') ?>
    <span><?= lang('Products.title') ?></span>
</a>
```

### Paso 10: Agregar Seguridad de Tipos

Asegúrate de que todos los archivos nuevos tengan `declare(strict_types=1);` al principio y usa type hints apropiados en parámetros y tipos de retorno.

---

## 🔗 Cómo Agregar un Elemento a la Barra Lateral

1. Abre `app/Views/layouts/partials/sidebar.php`.
2. Agrega un nuevo enlace de navegación usando el siguiente patrón:
   ```php
   <a href="<?= site_url('products') ?>" 
      class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= active_nav('products*') ?>">
       <?= ui_icon('zap') ?>
       <span><?= lang('App.products') ?></span>
   </a>
   ```
   - Usa `active_nav('path*')` para asegurar que el enlace se destaque en subpáginas.
   - Usa `ui_icon('name')` para elegir un icono.

---

## 📅 Cómo Manejar Nuevos Formatos de Fecha

Si necesitas un formato de fecha personalizado para una localidad específica:
1. Abre `app/Config/App.php`.
2. Actualiza el array `$dateFormats`:
   ```php
   public array $dateFormats = [
       'es' => 'd/m/Y H:i',
       'en' => 'm/d/Y h:i A',
   ];
   ```
   - El ayudante PHP `format_date()` y el ayudante JS `formatDate()` respetarán automáticamente estos ajustes.

---

## 🖼️ Cómo Cargar un Nuevo Tipo de Archivo

1. **Validación Frontend:** Abre `app/Modules/Files/Requests/FileUploadRequest.php` y actualiza las `rules()` para extensiones permitidas.
2. **Contrato API:** Asegúrate de que el Backend acepte el nuevo tipo MIME.
3. **Iconos:** Si quieres un icono específico para el tipo de archivo en la lista, actualiza `app/Helpers/ui_helper.php` mapeo de iconos.

---

## 🚨 Cómo Agregar Mapeo de Error de Campo Personalizado

Si la API devuelve una clave de error para un campo que es diferente del atributo `name` de tu formulario:
1. Abre tu Controlador.
2. Sobrescribe el método `normalizeErrorKey()`:
   ```php
   protected function normalizeErrorKey(string $key): string
   {
       if ($key === 'api_field_name') {
           return 'form_field_name';
       }
       return parent::normalizeErrorKey($key);
   }
   ```
