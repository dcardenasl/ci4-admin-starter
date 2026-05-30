<?php
/**
 * @var string $name
 * @var string $label
 * @var array $options Associative array of options [value => label]
 * @var mixed|null $value
 * @var bool|null $required
 * @var string|null $help
 */

helper('form');

$required = $required ?? false;
$value = old($name, $value ?? '');
$help = $help ?? '';
?>
<div>
    <span class="block text-sm font-medium text-gray-700">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </span>
    <?php if ($help): ?>
        <p class="text-xs text-gray-500 mt-1"><?= lang($help) ?></p>
    <?php endif; ?>
    
    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
        <?php foreach ($options as $val => $lbl): ?>
            <label class="inline-flex items-center gap-2 text-sm rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                <input 
                    type="radio" 
                    name="<?= esc($name, 'attr') ?>" 
                    value="<?= esc($val, 'attr') ?>"
                    <?= (string) $val === (string) $value ? 'checked' : '' ?>
                    <?= $required ? 'required' : '' ?>
                    <?= field_aria_attrs($name, $required) ?>
                    class="rounded-full border-gray-300 text-brand-600 focus:ring-brand-500"
                >
                <span class="font-medium text-gray-900"><?= esc($lbl) ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <?= render_field_error($name) ?>
</div>
