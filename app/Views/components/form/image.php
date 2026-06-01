<?php
/**
 * @var string $name
 * @var string $label
 * @var mixed|null $value
 * @var bool|null $required
 * @var string|null $help
 */

helper('form');

$required = $required ?? false;
$value = old($name, $value ?? '');
if (is_array($value)) {
    $value = '';
}
$value = (string) $value;
$help = $help ?? '';
?>
<?= view('components/form/file', [
    'name'       => $name,
    'value'      => $value,
    'label'      => $label,
    'required'   => $required,
    'accept'     => 'image/*',
    'filterType' => 'image',
    'help'       => $help,
]) ?>
