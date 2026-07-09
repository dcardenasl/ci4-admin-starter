<?php
/**
 * Shared loading state for list and detail refreshes.
 *
 * @var string      $title       Translation key for the main message.
 * @var string|null $description Translation key for the helper line.
 * @var string|null $icon        Lucide icon name.
 */

$title       = $title ?? 'App.loading';
$description = $description ?? 'App.loading_refreshing';
$icon        = $icon ?? 'loader';
?>
<div class="my-6 flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center shadow-sm">
    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
        <?= ui_icon($icon, 'h-6 w-6 animate-spin') ?>
    </div>
    <h3 class="text-base font-semibold text-gray-900"><?= esc(lang($title)) ?></h3>
    <p class="mt-2 max-w-md text-sm text-gray-500"><?= esc(lang($description)) ?></p>
</div>
