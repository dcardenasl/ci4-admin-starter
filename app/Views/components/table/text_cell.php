<?php
/**
 * @var string $value
 * @var int|null $limit
 */

$limit = $limit ?? 50;
$display = (strlen($value) > $limit) ? substr($value, 0, $limit) . '...' : $value;
?>
<span class="text-sm text-gray-900" title="<?= esc($value, 'attr') ?>">
    <?= esc($display) ?>
</span>
