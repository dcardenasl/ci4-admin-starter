<?php
/**
 * @var string $name
 * @var string $label
 * @var mixed|null $value
 * @var bool|null $required
 * @var string|null $accept
 * @var string|null $filterType
 * @var string|null $help
 */

helper('form');

$required = $required ?? false;
$value = old($name, $value ?? '');
if (is_array($value)) {
    $value = '';
}
$value = (string) $value;
$placeholder = $placeholder ?? '';
$help = $help ?? '';
$accept = $accept ?? '';
$filterType = $filterType ?? '';
?>
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>

    <?= view('layouts/partials/file_picker_field', [
        'name'       => $name,
        'value'      => $value,
        'label'      => lang($label),
        'accept'     => $accept,
        'filterType' => $filterType,
    ]) ?>

    <?php if ($help): ?>
        <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
    <?php endif; ?>
    <?= render_field_error($name) ?>
</div>
