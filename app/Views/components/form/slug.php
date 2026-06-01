<?php
/**
 * Auto-slug field component with live asynchronous check.
 *
 * @var string      $name       Field name (default: 'slug')
 * @var string|null $label      Field label (default: 'App.slug')
 * @var string|null $value      Default value (fallback to old input)
 * @var string      $sourceId   CSS selector of source input (e.g. '#name')
 * @var string      $checkUrl   Endpoint route to verify slug availability
 * @var mixed|null  $currentId   Current model ID to skip inside uniqueness check on edits
 * @var bool|null   $required   Whether the field is required (default: true)
 * @var string|null $help       Help text below the field
 */

helper('form');

$name      = $name ?? 'slug';
$label     = $label ?? 'App.slug';
$value     = old($name, $value ?? '');
$required  = $required ?? true;
$currentId = $currentId ?? '';
$help      = $help ?? '';

// Self-contained localization helper with fallback
if (!function_exists('safe_lang')) {
    function safe_lang(string $key, string $fallback): string
    {
        $val = lang($key);
        return ($val === $key) ? $fallback : $val;
    }
}
?>

<div data-slug-field>
    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name, 'attr') ?>">
        <?= esc(lang($label)) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>
    <div class="flex gap-2 mt-1">
        <input 
            id="<?= esc($name, 'attr') ?>" 
            name="<?= esc($name, 'attr') ?>" 
            type="text" 
            value="<?= esc($value) ?>" 
            class="<?= esc(input_class($name)) ?>"
            data-slug-source="<?= esc($sourceId, 'attr') ?>"
            data-slug-check-url="<?= esc($checkUrl, 'attr') ?>"
            data-slug-current-id="<?= esc((string) $currentId, 'attr') ?>"
            <?= $required ? 'required' : '' ?>
            minlength="2" 
            maxlength="255" 
            pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
            <?= field_aria_attrs($name, $required) ?>
        >
        <!-- Status icons indicating async check state -->
        <span class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg border bg-white" data-slug-status="checking" title="<?= esc(safe_lang('Catalog.slug_checking', 'Verificando...')) ?>" aria-label="Checking">
            <?= ui_icon('clock', 'h-3.5 w-3.5 text-gray-400') ?>
        </span>
        <span class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-green-200 bg-green-50 text-green-700" data-slug-status="available" title="<?= esc(safe_lang('Catalog.slug_available', 'Disponible')) ?>" aria-label="Available">
            <?= ui_icon('check', 'h-3.5 w-3.5') ?>
        </span>
        <span class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700" data-slug-status="unavailable" title="<?= esc(safe_lang('Catalog.slug_unavailable', 'No disponible')) ?>" aria-label="Unavailable">
            <?= ui_icon('x', 'h-3.5 w-3.5') ?>
        </span>
        <button type="button" class="<?= esc(action_button_class()) ?> px-3" data-slug-regenerate title="<?= esc(safe_lang('Catalog.button_regenerate_slug', 'Regenerar')) ?>" aria-label="Regenerate">
            <?= ui_icon('refresh-ccw', 'h-3.5 w-3.5') ?>
        </button>
    </div>
    
    <p class="mt-1 text-xs text-gray-500">
        <?= $help ? esc(lang($help)) : (safe_lang('Catalog.help_slug', 'Ejemplo: titulo-del-recurso')) ?>
    </p>
    <?= render_field_error($name) ?>
</div>
