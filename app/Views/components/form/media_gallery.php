<?php
/**
 * Reusable dynamic media list and gallery component.
 *
 * @var string      $name       Name of the array inputs (default: 'media')
 * @var array|null  $value      Array of media row objects
 * @var string|null $label      Title for the gallery section (default: lang('Catalog.field_media'))
 * @var string|null $help       Help text below the title
 */

helper('form');

$name  = $name ?? 'media';
$label = $label ?? '';
$help  = $help ?? '';

// Self-contained localization helper with fallback
if (!function_exists('safe_lang')) {
    function safe_lang(string $key, string $fallback): string
    {
        $val = lang($key);
        return ($val === $key) ? $fallback : $val;
    }
}

$label = $label !== '' ? lang($label) : safe_lang('Catalog.field_media', 'Galería Multimedia');
$help  = $help  !== '' ? lang($help) : safe_lang('Catalog.help_item_media', 'Administra las imágenes y videos de este recurso.');

// Recover old input if any, fallback to original value
$mediaRows = old($name, $value ?? []);
if (!is_array($mediaRows)) {
    $mediaRows = [];
}
if ($mediaRows === []) {
    $mediaRows = [[
        'type' => 'cover',
        'hub_file_id' => null,
        'external_url' => null,
        'alt_text' => null,
        'caption' => null,
        'sort_order' => 0,
        'is_active' => true,
    ]];
}
?>

<div class="mt-6" x-data="adminMediaGallery({ rows: <?= esc(json_encode(array_values($mediaRows), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), 'attr') ?> })" x-init="init()">
    <div class="flex items-center justify-between">
        <div>
            <h4 class="text-base font-semibold text-gray-900"><?= esc($label) ?></h4>
            <p class="mt-1 text-xs text-gray-500"><?= esc($help) ?></p>
        </div>
        <button type="button" class="<?= esc(action_button_class()) ?>" @click="addRow()">
            <?= ui_icon('plus', 'h-3.5 w-3.5') ?>
            <?= esc(safe_lang('Catalog.button_add_media', 'Añadir Medio')) ?>
        </button>
    </div>
    <?= render_field_error($name) ?>
    
    <div class="mt-3 space-y-3">
        <template x-for="(row, index) in rows" :key="index">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <input type="hidden" :name="`<?= esc($name, 'attr') ?>[${index}][hub_file_id]`" x-model="row.hub_file_id">
                
                <div class="grid gap-4 lg:grid-cols-[220px_minmax(0,1fr)]">
                    <!-- File Preview & Selector -->
                    <div class="space-y-3">
                        <div class="flex h-36 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <template x-if="row.file && row.file.is_image && row.file.url">
                                <img :src="row.file.url" :alt="fileName(row)" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!(row.file && row.file.is_image && row.file.url)">
                                <div class="text-gray-400">
                                    <?= ui_icon('image', 'h-10 w-10') ?>
                                </div>
                            </template>
                        </div>
                        <button type="button" class="<?= esc(action_button_class()) ?> w-full" @click="chooseFile(row)" x-show="row.type !== 'video'">
                            <?= ui_icon('upload', 'h-3.5 w-3.5') ?>
                            <span x-text="row.hub_file_id ? '<?= esc(safe_lang('Catalog.button_change_media_file', 'Cambiar Archivo')) ?>' : '<?= esc(safe_lang('Catalog.button_select_media_file', 'Seleccionar Archivo')) ?>'"></span>
                        </button>
                    </div>

                    <!-- Row Configuration Form -->
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    <?= esc(safe_lang('Catalog.field_media_type', 'Tipo')) ?>
                                </label>
                                <select :name="`<?= esc($name, 'attr') ?>[${index}][type]`" x-model="row.type" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                    <option value="cover"><?= esc(safe_lang('Catalog.option_cover', 'Portada')) ?></option>
                                    <option value="gallery"><?= esc(safe_lang('Catalog.option_gallery', 'Galería')) ?></option>
                                    <option value="video"><?= esc(safe_lang('Catalog.option_video', 'Video')) ?></option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    <?= esc(safe_lang('Catalog.field_media_sort_order', 'Orden')) ?>
                                </label>
                                <input :name="`<?= esc($name, 'attr') ?>[${index}][sort_order]`" x-model="row.sort_order" type="number" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>
                            <label class="mt-6 flex items-center gap-2 text-sm text-gray-700 select-none">
                                <input type="hidden" :name="`<?= esc($name, 'attr') ?>[${index}][is_active]`" value="0">
                                <input :name="`<?= esc($name, 'attr') ?>[${index}][is_active]`" x-model="row.is_active" type="checkbox" value="1" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span><?= esc(safe_lang('Catalog.field_media_is_active', 'Activo')) ?></span>
                            </label>
                        </div>
                        
                        <!-- External URL for Video type -->
                        <div x-show="row.type === 'video'">
                            <label class="block text-sm font-medium text-gray-700">
                                <?= esc(safe_lang('Catalog.field_media_external_url', 'Enlace del Video (YouTube/Vimeo)')) ?>
                            </label>
                            <input :name="`<?= esc($name, 'attr') ?>[${index}][external_url]`" x-model="row.external_url" type="url" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        </div>
                        <input type="hidden" :name="`<?= esc($name, 'attr') ?>[${index}][external_url]`" x-model="row.external_url" x-bind:disabled="row.type === 'video'">
                        
                        <!-- Alt Text & Caption fields -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    <?= esc(safe_lang('Catalog.field_media_alt_text', 'Texto Alternativo (Alt)')) ?>
                                </label>
                                <input :name="`<?= esc($name, 'attr') ?>[${index}][alt_text]`" x-model="row.alt_text" type="text" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    <?= esc(safe_lang('Catalog.field_media_caption', 'Título / Leyenda')) ?>
                                </label>
                                <input :name="`<?= esc($name, 'attr') ?>[${index}][caption]`" x-model="row.caption" type="text" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>
                        </div>
                        
                        <!-- Action buttons -->
                        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                            <button type="button" class="text-xs text-gray-500 hover:text-gray-900" x-show="row.hub_file_id" @click="clearFile(row)">
                                <?= esc(safe_lang('Catalog.button_clear_media_file', 'Limpiar Archivo')) ?>
                            </button>
                            <button type="button" class="text-xs text-red-600 hover:text-red-700 font-semibold" @click="removeRow(index)">
                                <?= esc(lang('App.remove') ?? 'Eliminar Fila') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
