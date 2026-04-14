# Guides & How-To

This document provides step-by-step instructions for common development tasks in the **CI4 Admin Starter**.

---

## 🛠️ How to Add a New Module

This project uses a **modular architecture**. To add a new feature (e.g., "Products"), follow these steps:

### Step 1: Backend Verification
Ensure the corresponding API endpoints exist in the Backend (`ci4-api-starter`).

### Step 2: Create Module Structure
Create the directory structure under `app/Modules/Products/`:

```
app/Modules/Products/
├── Config/
│   └── Routes.php                 # Module routes (prefix: products/)
├── Controllers/
│   └── ProductController.php      # Extends BaseWebController
├── Language/
│   ├── en/
│   │   └── Products.php           # English translations
│   └── es/
│       └── Products.php           # Spanish translations
├── Requests/                      # Only if form inputs exist
│   ├── ProductStoreRequest.php
│   └── ProductUpdateRequest.php
└── Services/
    ├── ProductApiService.php      # Extends BaseApiService
    └── ProductApiServiceInterface.php  # (REQUIRED) Interface for DI
```

### Step 3: Create Service Interface & Class
**`app/Modules/Products/Services/ProductApiServiceInterface.php`:**
```php
<?php
declare(strict_types=1);

namespace App\Modules\Products\Services;

interface ProductApiServiceInterface
{
    /** @return ApiResponse */
    public function list(array<string, mixed> $filters = []): array;
    
    /** @return ApiResponse */
    public function get(int|string $id): array;
    
    /** @return ApiResponse */
    public function create(array<string, mixed> $payload): array;
    
    /** @return ApiResponse */
    public function update(int|string $id, array<string, mixed> $payload): array;
    
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
    // Implement interface methods...
}
```

### Step 4: Register Service in `app/Config/Services.php`
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

### Step 5: Create Controller
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
        // Render product list...
    }
}
```

### Step 6: Create Language Files
**`app/Modules/Products/Language/en/Products.php`:**
```php
<?php
return [
    'title'       => 'Products',
    'description' => 'Manage your products',
    // Add more translations...
];
```

### Step 7: Create Views
**`app/Views/products/index.php`:**
```php
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <!-- Product listing view -->
<?= $this->endSection() ?>
```

### Step 8: Register Routes
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

### Step 9: Add to Navigation
Edit `app/Views/layouts/partials/sidebar.php` and add:
```php
<a href="<?= site_url('products') ?>"
   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= active_nav('products*') ?>">
    <?= ui_icon('box') ?>
    <span><?= lang('Products.title') ?></span>
</a>
```

### Step 10: Add Type Safety
Ensure all new files have `declare(strict_types=1);` at the top and use proper type hints on method parameters and return types.

---

## 🔗 How to Add an Item to the Sidebar

1.  Open `app/Views/layouts/partials/sidebar.php`.
2.  Add a new navigation link using the following pattern:
    ```php
    <a href="<?= site_url('products') ?>" 
       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= active_nav('products*') ?>">
        <?= ui_icon('zap') ?>
        <span><?= lang('App.products') ?></span>
    </a>
    ```
    - Use `active_nav('path*')` to ensure the link stays highlighted on sub-pages.
    - Use `ui_icon('name')` to choose an icon.

---

## 📅 How to Handle New Date Formats

If you need a custom date format for a specific locale:
1.  Open `app/Config/App.php`.
2.  Update the `$dateFormats` array:
    ```php
    public array $dateFormats = [
        'es' => 'd/m/Y H:i',
        'en' => 'm/d/Y h:i A',
    ];
    ```
    - The `format_date()` PHP helper and `formatDate()` JS helper will automatically respect these settings.

---

## 🖼️ How to Upload a New Type of File

1.  **Frontend Validation:** Open `app/Requests/File/FileUploadRequest.php` and update the `rules()` for allowed extensions.
2.  **API Contract:** Ensure the Backend accepts the new MIME type.
3.  **Icons:** If you want a specific icon for the file type in the list, update `app/Helpers/ui_helper.php` icon mapping.

---

## 🚨 How to Add Custom Field Error Mapping

If the API returns an error key for a field that is different from your form's `name` attribute:
1.  Open your Controller.
2.  Override the `normalizeErrorKey()` method:
    ```php
    protected function normalizeErrorKey(string $key): string
    {
        if ($key === 'api_field_name') {
            return 'form_field_name';
        }
        return parent::normalizeErrorKey($key);
    }
    ```
