<?php
$title ??= '';
$subtitle ??= null;
$actionsView ??= null;
?>
<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
        <?php if ($title !== ''): ?>
            <h3 class="text-lg font-semibold text-gray-900"><?= esc($title) ?></h3>
        <?php endif; ?>
        <?php if (is_string($subtitle) && $subtitle !== ''): ?>
            <p class="mt-1 text-sm text-gray-500"><?= esc($subtitle) ?></p>
        <?php endif; ?>
    </div>

    <?php if (is_string($actionsView) && $actionsView !== ''): ?>
        <div class="flex items-center gap-2">
            <?= $this->include($actionsView) ?>
        </div>
    <?php endif; ?>
</div>
