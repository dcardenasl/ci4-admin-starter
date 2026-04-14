# Guides & How-To

This document provides step-by-step instructions for common development tasks in the **CI4 Admin Starter**.

---

## 🛠️ How to Add a New Module

To add a complete new feature (e.g., "Products"), follow these steps:

1.  **Backend Verification:** Ensure the corresponding API endpoints exist in the Backend.
2.  **Service Interface:** Create `app/Services/ProductApiServiceInterface.php`.
3.  **Service Class:** Create `app/Services/ProductApiService.php` extending `BaseApiService`.
4.  **Register Service:** Add the binding in `app/Config/Services.php`:
    ```php
    public static function productsApi(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedUnitOfWork(__FUNCTION__);
        }
        return new \App\Services\ProductApiService(static::apiClient());
    }
    ```
5.  **Controller:** Create `app/Controllers/ProductController.php` extending `BaseWebController`.
6.  **FormRequests:** Create any necessary validation classes in `app/Requests/Product/`.
7.  **Views:** Create the module views in `app/Views/products/`.
8.  **Routes:** Register the routes in `app/Config/Routes.php`.
9.  **Navigation:** Add the link to `app/Views/layouts/partials/sidebar.php`.

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
