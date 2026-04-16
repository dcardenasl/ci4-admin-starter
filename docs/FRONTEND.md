# Frontend & UI Guide

This guide explains how to build and maintain the user interface of the **CI4 Admin Starter**. We prioritize a clean, accessible, and interactive experience using a modern, utility-first stack.

## 🛠️ The UI Stack

- **Framework:** CodeIgniter 4 (Server-Rendered Views).
- **Styling:** [Tailwind CSS](https://tailwindcss.com/) (Utility-first CSS).
- **Interactivity:** [Alpine.js](https://alpinejs.dev/) (Lightweight JavaScript framework).
- **Icons:** [Lucide Icons](https://lucide.dev/).
- **Fonts:** System sans-serif stack for performance.

---

## 🏗️ Layout System

The application uses a standard layout located in `app/Views/layouts/app.php`.

### Key Components:
- **Sidebar:** Navigation links, managed by the `appShell` Alpine component for mobile responsiveness.
- **Navbar:** Breadcrumbs, User Profile, and Language Switcher.
- **Flash Messages:** Server-side feedback (Success/Error).
- **Toast Notifications:** Client-side feedback via `Alpine.store('toast')`.
- **Confirm Modal:** A global confirmation dialog via `Alpine.store('confirm')`.

---

## ⚡ Alpine.js Integration

We use Alpine.js to add interactivity without the overhead of a heavy framework.

### Global Stores
1.  **`confirm`:** Used to show confirmation dialogs for destructive actions.
    ```javascript
    // Example: Triggering a confirmation from Alpine
    $store.confirm.show('¿Estás seguro?', () => {
        // Perform action (e.g., submit a form or fetch)
    });
    ```
2.  **`toast`:** Used for temporary notifications.
    ```javascript
    $store.toast.push('success', '¡Operación exitosa!');
    ```

### Interactive Tables (`remoteTable`)
The `remoteTable` component (`public/assets/js/app.js`) is a powerful tool for building server-driven data tables. It handles:
- **Filtering:** Automatic form binding and debounced search.
- **Sorting:** Clickable headers with visual indicators.
- **Pagination:** Support for Page-based and Cursor-based navigation.
- **Loading States:** Built-in indicators and error handling.
- **URL Synchronization:** Updates the browser URL so filters are shareable.

---

## 🧩 PHP UI Helpers

To maintain consistency and reduce Tailwind bloat in views, we use dedicated PHP helpers.

### `ui_helper.php`
Provides standard CSS classes and icons:
- `table_class()`, `table_th_class()`, `table_td_class()`: Consistent table styling.
- `action_button_class('primary'|'danger'|'neutral')`: Standardized buttons.
- `ui_icon('name')`: Renders a Lucide icon with proper accessibility.
- `format_date($date)`: Formats dates based on the user's locale.

### `badge_helper.php`
Standardizes the look of status indicators:
- `status_badge($status)`: Returns Tailwind classes for success/warning/danger colors.
- `role_badge($role)`: Highlights admin roles.
- `localized_status($status)`: Returns the translated label for a status.

### `form_helper.php`
Simplifies error handling in forms:
- `render_field_error('field_name')`: Automatically displays the error message if the API returns validation failures for that field.
- `field_error_class('field_name')`: Adds a red border to the input if it has an error.

---

## 🎨 Styling Conventions

1.  **Brand Colors:** We use a `brand` palette (defined in `tailwind.config.js`) for primary actions.
2.  **Consistency:** Avoid writing custom CSS. 99% of the UI should be built using Tailwind utilities or the provided PHP helpers.
3.  **Responsiveness:** Use `md:` and `lg:` prefixes to ensure the admin is usable on tablets and desktops. The sidebar automatically collapses on mobile.
4.  **Accessibility:**
    - Always use `aria-label` for icon-only buttons.
    - Use the `sr-only` class for labels that should only be read by screen readers.
    - Ensure sufficient color contrast for text.

---

## 🚀 Adding a New Page

1.  **Create the View:** Place it in `app/Views/your_module/your_page.php`.
2.  **Update the Sidebar:** Add the link in `app/Views/layouts/partials/sidebar.php`.
    - Use `active_nav('path/to/page')` to highlight the current link.
    - Use `ui_icon('icon_name')` for the sidebar icon.
3.  **Render the View:** In your Controller, use the `render()` helper:
    ```php
    return $this->render('your_module/your_page', [
        'title' => 'My New Page',
        // ... data
    ]);
    ```
