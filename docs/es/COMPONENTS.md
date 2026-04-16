# Librería de Componentes UI

Este documento describe los componentes UI reutilizables disponibles en la plantilla **CI4 Admin Starter**. Todos los componentes se construyen con clases de utilidad Tailwind CSS e interactividad de Alpine.js.

> **Consejo:** Las clases de componentes CSS globales (`.btn-primary`, `.form-input`, etc.) se definen en `app/Views/layouts/partials/head.php`.

---

## Botones

```php
<!-- Acción primaria -->
<button class="btn-primary">Guardar cambios</button>

<!-- Secundario / neutro -->
<button class="btn-secondary">Cancelar</button>

<!-- Acción destructiva -->
<button class="btn-danger">Eliminar</button>
```

**Cuándo usar:**
- `btn-primary` — CTA principal (uno por formulario/sección).
- `btn-secondary` — cancelar, atrás, o acciones secundarias.
- `btn-danger` — operaciones destructivas irreversibles (mostrar modal de confirmación primero).

---

## Entradas de Formulario

```php
<!-- Entrada de texto -->
<input type="email" name="email" class="form-input <?= has_field_error('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : '' ?>">

<!-- Mensaje de error -->
<?= field_error_html('email') ?>
```

**Funciones de ayuda** (`app/Helpers/form_helper.php`):
- `has_field_error(string $field): bool` — devuelve `true` si el campo tiene un error.
- `field_error_html(string $field): string` — renderiza `<p class="...">texto de error</p>` o string vacío.

---

## Tarjetas

```php
<div class="card">
    <h2 class="text-lg font-semibold text-gray-900">Título de tarjeta</h2>
    <p class="mt-1 text-sm text-gray-600">Contenido del cuerpo de la tarjeta.</p>
</div>
```

---

## Insignias

Usa funciones de `app/Helpers/badge_helper.php` para renderizar insignias de estado coloreadas.

```php
<!-- Insignia de estado (activo, pendiente, suspendido, rechazado, aprobado...) -->
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= status_badge($user['status']) ?>">
    <?= esc(localized_status($user['status'])) ?>
</span>

<!-- Insignia de rol -->
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= role_badge($user['role']) ?>">
    <?= esc(localized_role($user['role'])) ?>
</span>
```

Ayudantes de insignia disponibles:

| Función | Devuelve | Usado para |
|---------|----------|-----------|
| `status_badge(?string)` | Clases CSS | Estado de usuario/recurso |
| `localized_status(?string)` | String traducido | Estado legible por humanos |
| `role_badge(?string)` | Clases CSS | Rol de usuario |
| `localized_role(?string)` | String traducido | Rol legible por humanos |

---

## Mensajes Flash / Notificaciones Toast

Disparadas desde PHP vía `withSuccess()`, `withError()`, o flash de sesión directamente.

```php
// En un controlador:
return $this->withSuccess(redirect()->to('/dashboard'), lang('Users.create_success'));
return $this->withError(redirect()->back(), lang('Users.create_failed'));
```

El parcial `flash_messages.php` los renderiza automáticamente. Alpine.js maneja auto-descartar y la cola de toast.

**Desde JavaScript** (vía store Alpine):
```js
Alpine.store('toast').push('success', 'Operación completada.');
Alpine.store('toast').push('error', 'Algo salió mal.');
Alpine.store('toast').push('warning', 'Verifica tu entrada.');
Alpine.store('toast').push('info', 'No se hicieron cambios.');
```

---

## Modal de Confirmación

Un modal reutilizable impulsado por `Alpine.store('confirm')`.

```html
<!-- Botón disparador -->
<button
    @click="$store.confirm.show(
        '<?= lang('Users.delete_confirm') ?>',
        () => document.getElementById('delete-form-<?= $user['id'] ?>').submit()
    )"
    class="btn-danger"
>
    <?= lang('App.delete') ?>
</button>

<!-- Formulario oculto para la acción real -->
<form id="delete-form-<?= $user['id'] ?>" method="post" action="<?= route_to('users.delete', $user['id']) ?>">
    <?= csrf_field() ?>
</form>
```

El parcial del modal se incluye una vez en `layouts/app.php` vía `confirm_modal.php`.
