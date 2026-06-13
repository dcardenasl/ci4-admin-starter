<?php
/**
 * @var string $name
 * @var string $label
 * @var mixed|null $value
 * @var bool|null $required
 * @var string|null $help
 * @var string|null $on_label
 * @var string|null $off_label
 */

declare(strict_types=1);

helper('form');

$required = $required ?? false;
$value = old($name, $value ?? false);
$help = $help ?? '';
$on_label = $on_label ?? 'App.yes';
$off_label = $off_label ?? 'App.no';
$checked = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

if ($checked === null) {
    $checked = (bool) $value;
}
?>
<div>
    <span class="block text-sm font-medium text-gray-700">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </span>

    <input type="hidden" name="<?= esc($name, 'attr') ?>" value="0">

    <label class="mt-2 inline-flex cursor-pointer items-center gap-3" x-data="{ checked: <?= $checked ? 'true' : 'false' ?> }">
        <input
            id="<?= esc($name, 'attr') ?>"
            name="<?= esc($name, 'attr') ?>"
            type="checkbox"
            value="1"
            class="peer sr-only"
            x-model="checked"
            <?= $required ? 'required' : '' ?>
            <?= field_aria_attrs($name, $required) ?>
        >
        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-gray-200 transition-colors duration-200 ease-in-out peer-checked:bg-brand-600 peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2">
            <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-in-out" :class="checked ? 'translate-x-5' : 'translate-x-0.5'"></span>
        </span>
        <span class="text-sm font-medium text-gray-700" x-text="checked ? '<?= esc(lang($on_label), 'js') ?>' : '<?= esc(lang($off_label), 'js') ?>'">
            <?= esc($checked ? (string) lang($on_label) : (string) lang($off_label)) ?>
        </span>
    </label>

    <?php if ($help): ?>
        <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
    <?php endif; ?>
    <?= render_field_error($name) ?>
</div>
