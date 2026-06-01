# Biblioteca de Componentes UI (ci4-admin-starter)

Esta carpeta contiene la colección de vistas reutilizables para el panel de administración. Proporcionan un diseño visual consistente usando Tailwind CSS y son consumidos por el generador de scaffolding automático (`make-module.sh`).

## Contratos de Variables Generales

Todos los componentes de formulario (`components/form/`) deben respetar los siguientes parámetros estándar para asegurar su compatibilidad con el motor de scaffolding:

*   `$name` (string, requerido): El nombre del campo HTML (`name="..."`).
*   `$label` (string, requerido): La clave de idioma (ej: `'Catalog.title'`) que se pasará a `lang()`.
*   `$value` (mixed, opcional): Valor actual del campo. Se resolverá internamente con `old($name, $value ?? '')`.
*   `$required` (bool, opcional): Si es `true`, renderiza un asterisco rojo y el atributo HTML `required`.
*   `$errors` (array, opcional): Array de errores de validación de la sesión para mostrar mensajes de error inline.
*   `$placeholder` (string, opcional): Texto de ayuda/placeholder.
*   `$help` (string, opcional): Texto de ayuda pequeño debajo del input.

---

## 1. Componentes de Formulario (`components/form/`)

### `text.php`
Input básico de texto de una sola línea.
```php
<?= view('components/form/text', [
    'name' => 'title',
    'label' => 'Catalog.title',
    'required' => true,
    'errors' => $errors
]) ?>
```

### `number.php`
Input para valores enteros. Soporta `$min`, `$max` y `$step`.
```php
<?= view('components/form/number', [
    'name' => 'quantity',
    'label' => 'Catalog.quantity',
    'min' => 0,
    'step' => 1,
    'errors' => $errors
]) ?>
```

### `decimal.php`
Input adaptado para importes numéricos decimales (monedas, dimensiones, etc.).
```php
<?= view('components/form/decimal', [
    'name' => 'price',
    'label' => 'Catalog.price',
    'step' => '0.01',
    'errors' => $errors
]) ?>
```

### `textarea.php`
Campo de texto multilínea con control de tamaño ajustable. Soporta `$rows` (por defecto 4).
```php
<?= view('components/form/textarea', [
    'name' => 'description',
    'label' => 'Catalog.description',
    'rows' => 5,
    'errors' => $errors
]) ?>
```

### `select.php`
Lista de selección estática a partir de un array asociativo `$options`.
```php
<?= view('components/form/select', [
    'name' => 'status',
    'label' => 'Catalog.status',
    'options' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived'
    ],
    'errors' => $errors
]) ?>
```

### `relation.php`
Selector de relaciones. Soporta dos modalidades:
1. **Estático (Por defecto):** Requiere un array asociativo `$options` precargado desde el controlador.
2. **Asíncrono:** Añadiendo `'async' => true` y `'api_endpoint' => '/api/v1/resource'`, inicializa un selector de búsqueda dinámica usando Alpine.js.
```php
<?= view('components/form/relation', [
    'name' => 'category_id',
    'label' => 'Catalog.category',
    'options' => $categories, // Estático
    'errors' => $errors
]) ?>
```

### `boolean.php`
Interruptor deslizante de tipo Toggle estructurado con Alpine.js.
```php
<?= view('components/form/boolean', [
    'name' => 'is_visible',
    'label' => 'Catalog.is_visible',
    'value' => true,
    'errors' => $errors
]) ?>
```

### `date.php`
Selector de fechas nativo.
```php
<?= view('components/form/date', [
    'name' => 'published_at',
    'label' => 'Catalog.published_at',
    'errors' => $errors
]) ?>
```

### `datetime.php`
Selector de fecha y hora nativo (`datetime-local`).
```php
<?= view('components/form/datetime', [
    'name' => 'start_time',
    'label' => 'Events.start_time',
    'errors' => $errors
]) ?>
```

### `file.php`
Selector de archivos integrado de forma nativa con el modal `FilePicker` de la plataforma.
```php
<?= view('components/form/file', [
    'name' => 'attachment',
    'label' => 'App.attachment',
    'errors' => $errors
]) ?>
```

### `image.php`
Variante del selector de archivos con previsualización responsive de la imagen seleccionada.
```php
<?= view('components/form/image', [
    'name' => 'cover',
    'label' => 'Catalog.cover',
    'errors' => $errors
]) ?>
```

### `tags.php`
Control dinámico con chips y agregador para el manejo de etiquetas múltiples.
```php
<?= view('components/form/tags', [
    'name' => 'tags',
    'label' => 'Catalog.tags',
    'errors' => $errors
]) ?>
```

---

## 2. Componentes de Celda de Tabla (`components/table/`)

Utilizados en el `index.php` de cada recurso para formatear correctamente los campos.

*   `text_cell.php`: Texto plano truncado.
*   `badge_cell.php`: Estado visual enriquecido con badge Tailwind de color dinámico.
*   `boolean_cell.php`: Check o Cruz estilizados.
*   `date_cell.php`: Fecha formateada de manera uniforme.
*   `image_cell.php`: Thumbnail cuadrado de relación fija 1:1.
*   `number_cell.php`: Valores numéricos estilizados y alineados a la derecha.

---

## 3. Componentes de Visualización (`components/display/`)

*   `field_row.php`: Fila clave/valor estructurada para vistas detalladas (`show.php`).
*   `empty_state.php`: Indicador elegante para tablas vacías.
*   `confirm_modal.php`: Modal global de confirmación de operaciones.
*   `reorder.php`: Componente interactivo para reordenamiento visual de elementos (Drag & Drop) mediante Alpine.js con persistencia asíncrona.
    ```php
    <?= view('components/display/reorder', [
        'items' => $items,
        'saveUrl' => route_to('admin.resource.save_order'),
        'displayKey' => 'title',
        'grouped' => false,
        'backUrl' => route_to('admin.resource')
    ]) ?>
    ```

---

## 4. Nuevos Componentes Avanzados de Formulario (`components/form/`)

### `metadata.php`
Editor visual interactivo de clave-valor para campos de metadatos almacenados como objetos JSON.
```php
<?= view('components/form/metadata', [
    'name' => 'metadata',
    'label' => 'App.metadata',
    'value' => $item['metadata'] ?? [],
    'help' => 'Catalog.help_metadata'
]) ?>
```

### `slug.php`
Campo de texto para slugs auto-generados a partir de un campo origen, con verificación dinámica de disponibilidad asíncrona mediante API.
```php
<?= view('components/form/slug', [
    'name' => 'slug',
    'label' => 'Catalog.field_slug',
    'value' => $item['slug'] ?? '',
    'sourceId' => '#title', // ID del input origen
    'checkUrl' => route_to('admin.catalog.items.check_slug'),
    'currentId' => $item['id'] ?? '',
    'required' => true
]) ?>
```

### `media_gallery.php`
Rejilla interactiva para administrar colecciones multimedia (imágenes, videos) integrada con el FilePicker y campos de metadatos específicos por fila.
```php
<?= view('components/form/media_gallery', [
    'name' => 'media',
    'label' => 'Catalog.field_media',
    'value' => $item['media'] ?? [],
    'help' => 'Catalog.help_item_media'
]) ?>
```
