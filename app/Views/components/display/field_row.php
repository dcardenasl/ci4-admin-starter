<?php
/**
 * @var string $label Language key or raw string
 * @var mixed $value Value to render
 * @var bool|null $isHtml Whether to render as raw HTML (default: false)
 */

$isHtml = $isHtml ?? false;
?>
<div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-b border-gray-100 last:border-0">
    <dt class="text-sm font-medium text-gray-500">
        <?= lang($label) ?>
    </dt>
    <dd class="mt-1 min-w-0 break-words text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">
        <?php if ($isHtml): ?>
            <?= $value ?>
        <?php else: ?>
            <?= esc($value ?? '—') ?>
        <?php endif; ?>
    </dd>
</div>
