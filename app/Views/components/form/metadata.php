<?php
/**
 * Generic JSON metadata key-value editor component.
 *
 * @var string      $name         The name of the hidden input field (default: 'metadata')
 * @var string|null $label        Field label to translate via lang() (default: 'App.metadata')
 * @var mixed|null  $value        Current metadata value (supports array, object, or JSON string)
 * @var string|null $help         Help text displayed below the field label
 */

helper('form');

$name  = $name ?? 'metadata';
$label = $label ?? 'App.metadata';
$help  = $help ?? '';

// Self-contained localization helper with fallback
if (!function_exists('safe_lang')) {
    function safe_lang(string $key, string $fallback): string
    {
        $val = lang($key);
        return ($val === $key) ? $fallback : $val;
    }
}

// Handle old inputs and default formatting
$metadataValue = old($name, $value ?? []);
if (is_array($metadataValue) || is_object($metadataValue)) {
    $metadataValue = json_encode($metadataValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
}
if (!is_string($metadataValue) || trim($metadataValue) === '') {
    $metadataValue = '{}';
}

$metadataDecoded = json_decode($metadataValue, true) ?: [];
$metadataRows = [];

if (is_array($metadataDecoded)) {
    foreach ($metadataDecoded as $k => $v) {
        $metadataRows[] = [
            'key' => (string) $k,
            'value' => is_scalar($v) || $v === null
                ? (string) $v
                : json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }
}

if ($metadataRows === []) {
    $metadataRows = [['key' => '', 'value' => '']];
}
?>

<div class="mt-4" x-data="adminMetadataField({ rows: <?= esc(json_encode($metadataRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), 'attr') ?> })" x-init="init()">
    <input type="hidden" id="<?= esc($name, 'attr') ?>" name="<?= esc($name, 'attr') ?>" :value="json">
    
    <div class="flex items-center justify-between gap-3">
        <label class="block text-sm font-medium text-gray-700">
            <?= esc(lang($label)) ?>
        </label>
        <div class="flex gap-2">
            <button type="button" class="<?= esc(action_button_class()) ?> px-3.5 py-2 text-xs font-semibold" @click="importJson()">
                <?= ui_icon('upload', 'h-3.5 w-3.5') ?>
                <?= esc(safe_lang('Catalog.button_import_json', 'Importar JSON')) ?>
            </button>
            <button type="button" class="<?= esc(action_button_class('primary')) ?> px-3.5 py-2 text-xs font-semibold" @click="addRow()">
                <?= ui_icon('plus', 'h-3.5 w-3.5') ?>
                <?= esc(safe_lang('Catalog.button_add_attribute', 'Añadir Fila')) ?>
            </button>
        </div>
    </div>

    <div class="mt-2 space-y-2">
        <template x-for="(row, index) in rows" :key="index">
            <div class="grid gap-2 grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_auto] items-start">
                <div class="relative">
                    <input type="text" x-model="row.key" @input="sync()" 
                           :class="duplicates.includes(index) ? '<?= esc(input_class($name)) ?> !border-red-500 !ring-red-200' : '<?= esc(input_class($name)) ?>'" 
                           placeholder="<?= esc(safe_lang('Catalog.field_attribute_key', 'Clave')) ?>">
                    <template x-if="duplicates.includes(index)">
                        <span class="absolute right-2 top-1/2 -translate-y-1/2 text-red-500" title="<?= esc(safe_lang('Catalog.error_duplicate_key', 'Clave duplicada')) ?>">
                            <?= ui_icon('triangle-alert', 'h-4 w-4') ?>
                        </span>
                    </template>
                </div>
                <div class="relative">
                    <input type="text" x-model="row.value" @input="sync()" 
                           class="<?= esc(input_class($name)) ?>" 
                           placeholder="<?= esc(safe_lang('Catalog.field_attribute_value', 'Valor')) ?>">
                </div>
                <button type="button" class="<?= esc(action_button_class()) ?> px-3 mt-1" 
                        @click="removeRow(index)" aria-label="<?= esc(lang('App.remove')) ?>">
                    <?= ui_icon('x', 'h-3.5 w-3.5') ?>
                </button>
            </div>
        </template>
    </div>

    <?php if ($help): ?>
        <p class="mt-1 text-xs text-gray-500"><?= esc(lang($help)) ?></p>
    <?php endif; ?>

    <details class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
        <summary class="cursor-pointer text-sm font-medium text-gray-700 flex items-center gap-2 select-none">
            <?= ui_icon('database', 'h-3.5 w-3.5') ?>
            <?= esc(safe_lang('Catalog.field_metadata_raw', 'Ver JSON crudo')) ?>
        </summary>
        <pre class="mt-2 max-h-48 overflow-auto rounded bg-white p-3 text-xs text-gray-700 font-mono shadow-inner" x-text="json"></pre>
    </details>
    <?= render_field_error($name) ?>
</div>
