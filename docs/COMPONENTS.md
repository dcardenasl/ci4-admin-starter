# UI Component Library

This document describes the reusable view components available in the CI4 Admin Starter template. Components provide a consistent, standardized approach to building admin forms, tables, and display layouts with Tailwind CSS and Alpine.js.

> **Quick start:** See `app/Views/components/README.md` for detailed component contracts and usage examples.

---

## Component Organization

All reusable components are organized into three categories:

### 1. Form Components (`app/Views/components/form/`)

Standardized input fields with built-in validation feedback, help text, and accessibility features. Consumed by module scaffolding and manually integrated into create/edit forms.

**Types:**
- Basic inputs: `text`, `textarea`, `number`, `decimal`
- Temporal: `date`, `datetime`
- Selection: `select`, `radio`, `boolean`, `relation`
- Files: `file`, `image`, `media_gallery`
- Advanced: `tags`, `slug`, `metadata`

**Contract:**
All form components accept:
- `$name` (string, required) — HTML field name
- `$label` (string, required) — language key (e.g., `'Catalog.title'`)
- `$value` (mixed, optional) — current value, merged with `old($name, $value)`
- `$required` (bool, optional) — show asterisk and HTML `required` attribute
- `$errors` (array, optional) — validation errors from session
- `$help` (string, optional) — help text below the field
- Type-specific: `$min`, `$max`, `$step`, `$rows`, `$options`, `$async`, `$api_endpoint`, etc.

### 2. Table Components (`app/Views/components/table/`)

Cell renderers and list utilities for consistent table presentation, filtering, sorting, and pagination.

**Cell types:**
- `text_cell` — plain text, truncated
- `badge_cell` — colored status badge with dynamic styling
- `boolean_cell` — checkmark or cross
- `date_cell` — uniform date formatting
- `image_cell` — thumbnail with fixed 1:1 aspect ratio
- `number_cell` — right-aligned numeric display

**Utilities:**
- `toolbar` — filter toggles and action buttons
- `filter_panel` — collapsible filter controls
- `pagination` — page navigation with summary

### 3. Display Components (`app/Views/components/display/`)

Reusable display patterns for show views, empty states, modals, and interactions.

**Types:**
- `field_row` — key/value display for detail views
- `empty_state` — elegant empty-table indicator
- `confirm_modal` — global confirmation dialog
- `reorder` — drag-and-drop list manager with async persistence

---

## Legacy Component Classes

> **Note:** These Tailwind utility classes predate the component library. They remain for backward compatibility but should not be used in new code. Use the component system instead.

## Buttons

```php
<!-- Primary action -->
<button class="btn-primary">Save changes</button>

<!-- Secondary / neutral -->
<button class="btn-secondary">Cancel</button>

<!-- Destructive action -->
<button class="btn-danger">Delete</button>
```

**When to use:**
- `btn-primary` — main CTA (one per form/section).
- `btn-secondary` — cancel, back, or secondary actions.
- `btn-danger` — irreversible destructive operations (show a confirmation modal first).

---

## Form Inputs

```php
<!-- Text input -->
<input type="email" name="email" class="form-input <?= has_field_error('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : '' ?>">

<!-- Error message -->
<?= field_error_html('email') ?>
```

**Helper functions** (`app/Helpers/form_helper.php`):
- `has_field_error(string $field): bool` — returns `true` if the field has an error.
- `field_error_html(string $field): string` — renders `<p class="...">error text</p>` or empty string.

---

## Cards

```php
<div class="card">
    <h2 class="text-lg font-semibold text-gray-900">Card title</h2>
    <p class="mt-1 text-sm text-gray-600">Card body content.</p>
</div>
```

---

## Badges

Use `app/Helpers/badge_helper.php` functions to render colored status badges.

```php
<!-- Status badge (active, pending, suspended, rejected, approved...) -->
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= status_badge($user['status']) ?>">
    <?= esc(localized_status($user['status'])) ?>
</span>

<!-- Role badge -->
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= role_badge($user['role']) ?>">
    <?= esc(localized_role($user['role'])) ?>
</span>

<!-- Audit action badge (create, update, delete, login, logout...) -->
<span class="... <?= audit_action_badge($log['action']) ?>">
    <?= esc(localized_audit_action($log['action'])) ?>
</span>

<!-- Audit severity badge (info, warning, critical) -->
<span class="... <?= audit_severity_badge($log['severity']) ?>">
    <?= esc(localized_audit_severity($log['severity'])) ?>
</span>
```

Available badge helpers:

| Function | Returns | Used for |
|----------|---------|----------|
| `status_badge(?string)` | CSS classes | User/resource status |
| `localized_status(?string)` | Translated string | Human-readable status |
| `role_badge(?string)` | CSS classes | User role |
| `localized_role(?string)` | Translated string | Human-readable role |
| `audit_action_badge(?string)` | CSS classes | Audit log action |
| `localized_audit_action(?string)` | Translated string | Human-readable action |
| `audit_result_badge(?string)` | CSS classes | Audit log result |
| `localized_audit_result(?string)` | Translated string | Human-readable result |
| `audit_severity_badge(?string)` | CSS classes | Audit log severity |
| `localized_audit_severity(?string)` | Translated string | Human-readable severity |
| `health_tone_badge(?string)` | `{dot, text, bg}` array | API health indicator |

---

## Flash Messages / Toast Notifications

Triggered from PHP via `withSuccess()`, `withError()`, or session flash directly.

```php
// In a controller:
return $this->withSuccess(redirect()->to('/dashboard'), lang('Users.create_success'));
return $this->withError(redirect()->back(), lang('Users.create_failed'));
```

The `flash_messages.php` partial auto-renders them. Alpine.js handles auto-dismiss and the toast queue.

**From JavaScript** (via Alpine store):
```js
Alpine.store('toast').push('success', 'Operation completed.');
Alpine.store('toast').push('error', 'Something went wrong.');
Alpine.store('toast').push('warning', 'Check your input.');
Alpine.store('toast').push('info', 'No changes were made.');
```

---

## Confirmation Modal

A reusable modal powered by `Alpine.store('confirm')`.

```html
<!-- Trigger button -->
<button
    @click="$store.confirm.show(
        '<?= lang('Users.delete_confirm') ?>',
        () => document.getElementById('delete-form-<?= $user['id'] ?>').submit()
    )"
    class="btn-danger"
>
    <?= lang('App.delete') ?>
</button>

<!-- Hidden form for the actual action -->
<form id="delete-form-<?= $user['id'] ?>" method="post" action="<?= route_to('users.delete', $user['id']) ?>">
    <?= csrf_field() ?>
</form>
```

The modal partial is included once in `layouts/app.php` via `confirm_modal.php`.

---

## Tables (Server-Driven)

Tables use the `tableManager()` Alpine component for sort, pagination, and search.

```php
<div x-data="tableManager('<?= route_to('users.data') ?>')">
    <!-- Toolbar (search + actions) -->
    <?= view('layouts/partials/table_toolbar', ['searchPlaceholder' => lang('Users.search_placeholder')]) ?>

    <!-- Filter panel -->
    <?= view('layouts/partials/filter_panel', ['filtersView' => 'users/partials/filters']) ?>

    <!-- Table -->
    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th>
                    <button @click="sortBy('name')" ...>Name</button>
                </th>
            </tr>
        </thead>
        <tbody>
            <template x-for="row in rows" :key="row.id">
                <tr> ... </tr>
            </template>
        </tbody>
    </table>

    <!-- Pagination -->
    <?= view('layouts/partials/remote_pagination') ?>
</div>
```

See `app/Views/users/index.php` for a complete real-world example.

---

## Pagination

**Client-side (remote)** — used with `tableManager()`:
```php
<?= view('layouts/partials/remote_pagination') ?>
```

**Server-side** — used with standard CI4 Pager:
```php
<?= view('layouts/partials/pagination', ['pager' => $pager]) ?>
```

---

## Icons

Uses [Lucide Icons](https://lucide.dev) via CDN. Render with the `ui_icon()` helper:

```php
<?= ui_icon('users') ?>        <!-- users icon -->
<?= ui_icon('file-text') ?>    <!-- file icon -->
<?= ui_icon('settings') ?>     <!-- settings icon -->
```

The helper maps semantic names to Lucide icon names and renders an `<i>` tag with the `data-lucide` attribute. Icons are hydrated on DOM load by `bootLucideIcons()` in `app.js`.

---

## How to Add a New Module

See `docs/HOW-TO.md` for the full step-by-step guide. Quick summary:

1. Create `app/Modules/{ModuleName}/Controllers/{ModuleName}Controller.php` (extend `BaseWebController`).
2. Create `app/Modules/{ModuleName}/Config/Routes.php` with `['filter' => 'auth']` (add `'admin'` for admin-only routes).
3. Create `app/Modules/{ModuleName}/Services/{ModuleName}ApiService.php` (extend `BaseApiService`).
4. Create `app/Modules/{ModuleName}/Requests/` with FormRequest validation classes (extend `BaseFormRequest`).
5. Register the service in `app/Config/Services.php`.
6. Create views in `app/Views/{module_name}/`.
7. Add sidebar link in `app/Views/layouts/partials/sidebar.php`.
8. Add language strings to `app/Modules/{ModuleName}/Language/en/{ModuleName}.php` and `app/Modules/{ModuleName}/Language/es/{ModuleName}.php`.
