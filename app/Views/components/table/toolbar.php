<?php
/**
 * @var string $title
 * @var string|null $subtitle
 * @var string|null $actions Raw HTML actions or view path
 */

$title ??= '';
$subtitle ??= null;
$actions ??= null;
?>
<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
    <div>
        <?php if ($title !== ''): ?>
            <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang($title)) ?></h3>
        <?php endif; ?>
        <?php if (is_string($subtitle) && $subtitle !== ''): ?>
            <p class="mt-1 text-sm text-gray-500"><?= esc(lang($subtitle)) ?></p>
        <?php endif; ?>
    </div>

    <?php if (is_string($actions) && $actions !== ''): ?>
        <div class="flex items-center gap-2">
            <?php if (str_contains($actions, '/') || str_contains($actions, '\\')): ?>
                <?= $this->include($actions) ?>
            <?php else: ?>
                <?= $actions ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
