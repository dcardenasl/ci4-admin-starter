<?php
/**
 * Two-column admin resource layout.
 *
 * @var string $main Main column HTML.
 * @var string|null $aside Sidebar HTML.
 * @var string|null $mainClass Extra classes for main column.
 * @var string|null $asideClass Extra classes for aside column.
 */

$main       = $main       ?? '';
$aside      = $aside      ?? '';
$mainClass  = $mainClass  ?? 'space-y-6';
$asideClass = $asideClass ?? 'space-y-6';
?>
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 <?= esc($mainClass) ?>">
        <?= $main ?>
    </div>

    <?php if (trim((string) $aside) !== ''): ?>
        <aside class="<?= esc($asideClass) ?>">
            <?= $aside ?>
        </aside>
    <?php endif; ?>
</div>
