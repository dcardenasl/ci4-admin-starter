# Guía Frontend y UI

Esta guía explica cómo construir y mantener la interfaz de usuario de **CI4 Admin Starter**. Priorizamos una experiencia limpia, accesible e interactiva usando un stack moderno y basado en utilidades.

## 🛠️ El Stack UI

- **Framework:** CodeIgniter 4 (Vistas Renderizadas en Servidor).
- **Estilos:** [Tailwind CSS](https://tailwindcss.com/) (CSS basado en utilidades).
- **Interactividad:** [Alpine.js](https://alpinejs.dev/) (Framework JavaScript ligero).
- **Iconos:** [Iconos Lucide](https://lucide.dev/).
- **Fuentes:** Stack sans-serif del sistema para rendimiento.

---

## 🏗️ Sistema de Layout

La aplicación usa un layout estándar ubicado en `app/Views/layouts/app.php`.

### Componentes Clave:
- **Barra Lateral:** Enlaces de navegación, administrados por el componente Alpine `appShell` para capacidad de respuesta mobile.
- **Navbar:** Breadcrumbs, Perfil de Usuario, y Selector de Idioma.
- **Mensajes Flash:** Retroalimentación del lado del servidor (Éxito/Error).
- **Notificaciones Toast:** Retroalimentación del lado del cliente vía `Alpine.store('toast')`.
- **Modal de Confirmación:** Diálogo de confirmación global vía `Alpine.store('confirm')`.

---

## ⚡ Integración con Alpine.js

Usamos Alpine.js para agregar interactividad sin la sobrecarga de un framework pesado.

### Stores Globales
1. **`confirm`:** Usado para mostrar diálogos de confirmación para acciones destructivas.
   ```javascript
   // Ejemplo: Disparar una confirmación desde Alpine
   $store.confirm.show(lang('App.are_you_sure'), () => {
       // Realizar acción (p.ej., enviar formulario o fetch)
   });
   ```
2. **`toast`:** Usado para notificaciones temporales.
   ```javascript
   $store.toast.push('success', lang('App.operation_successful'));
   ```

### Tablas Interactivas (`remoteTable`)
El componente `remoteTable` (`public/assets/js/app.js`) es una herramienta poderosa para construir tablas de datos impulsadas por servidor. Maneja:
- **Filtrado:** Enlace automático de formulario y búsqueda debounced.
- **Ordenamiento:** Encabezados clickeables con indicadores visuales.
- **Paginación:** Soporte para navegación basada en Página y Cursor.
- **Estados de Carga:** Indicadores incorporados y manejo de errores.
- **Sincronización de URL:** Actualiza la URL del navegador para que los filtros sean compartibles.

---

## 🧩 Ayudantes UI de PHP

Para mantener consistencia y reducir inflado de Tailwind en vistas, usamos ayudantes PHP dedicados.

### `ui_helper.php`
Proporciona clases CSS estándar e iconos:
- `table_class()`, `table_th_class()`, `table_td_class()`: Estilo de tabla consistente.
- `action_button_class('primary'|'danger'|'neutral')`: Botones estandarizados.
- `ui_icon('name')`: Renderiza un icono Lucide con accesibilidad apropiada.
- `format_date($date)`: Formatea fechas basadas en la localidad del usuario.

### `badge_helper.php`
Estandariza la apariencia de indicadores de estado:
- `status_badge($status)`: Devuelve clases Tailwind para colores éxito/advertencia/peligro.
- `role_badge($role)`: Destaca roles de admin.
- `localized_status($status)`: Devuelve la etiqueta traducida para un estado.

### `form_helper.php`
Simplifica el manejo de errores en formularios:
- `render_field_error('field_name')`: Automáticamente muestra el mensaje de error si la API devuelve fallos de validación para ese campo.
- `field_error_class('field_name')`: Agrega un borde rojo a la entrada si tiene un error.

---

## 🎨 Convenciones de Estilos

1. **Colores de Marca:** Usamos una paleta `brand` (definida en `src/css/app.css` dentro del bloque `@theme`, sobrescribible en runtime desde `app/Views/layouts/partials/head.php`) para acciones primarias.
2. **Consistencia:** Evita escribir CSS personalizado. El 99% de la UI debe construirse usando utilidades Tailwind o los ayudantes PHP proporcionados.
3. **Capacidad de Respuesta:** Usa prefijos `md:` y `lg:` para asegurar que el admin sea utilizable en tablets y desktops. La barra lateral se colapsa automáticamente en mobile.
4. **Accesibilidad:**
   - Siempre usa `aria-label` para botones solo con icono.
   - Usa la clase `sr-only` para etiquetas que solo deben ser leídas por screen readers.
   - Asegura contraste suficiente de color para el texto.

---

## 🚀 Agregar una Nueva Página

1. **Crear la Vista:** Colócala en `app/Views/your_module/your_page.php`.
2. **Actualizar la Barra Lateral:** Agrega el enlace en `app/Views/layouts/partials/sidebar.php`.
   - Usa `active_nav('path/to/page')` para destacar el enlace actual.
   - Usa `ui_icon('icon_name')` para el icono de la barra lateral.
3. **Renderizar la Vista:** En tu Controlador, usa el ayudante `render()`:
   ```php
   return $this->render('your_module/your_page', [
       'title' => 'Mi Nueva Página',
       // ... datos
   ]);
   ```
