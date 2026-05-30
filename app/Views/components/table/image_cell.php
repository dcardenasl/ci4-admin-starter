<?php
/**
 * @var string|null $value
 * @var string|null $alt
 */

$alt = $alt ?? '';
?>
<div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-md border border-gray-200 bg-gray-50 flex items-center justify-center">
    <?php if (!empty($value)): ?>
        <img class="h-full w-full object-cover" src="<?= esc($value, 'attr') ?>" alt="<?= esc($alt, 'attr') ?>">
    <?php else: ?>
        <?= ui_icon('file', 'h-5 w-5 text-gray-400') ?>
    <?php endif; ?>
</div>
