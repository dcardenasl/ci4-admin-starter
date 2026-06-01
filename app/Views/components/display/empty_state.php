<?php
/**
 * Standard visual empty state component.
 *
 * @var string      $title         Title key for translations (default: 'App.no_results')
 * @var string|null $description   Description key for translations (default: 'App.no_results_desc')
 * @var string|null $icon          Lucide icon name (default: 'info')
 * @var string|null $actionUrl     Optional URL for primary action button
 * @var string|null $actionLabel   Optional translation key for action button label
 */

$title       = $title ?? 'App.no_results';
$description = $description ?? 'App.no_results_desc';
$icon        = $icon ?? 'info';
$actionUrl   = $actionUrl ?? null;
$actionLabel = $actionLabel ?? null;
?>
<div class="my-6 flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white text-center shadow-sm"
     style="padding-top: 4rem; padding-bottom: 4rem; padding-left: 2.5rem; padding-right: 2.5rem; margin-top: 2rem; margin-bottom: 2rem;">
    <!-- Centered high-contrast visual icon container with hover animation -->
    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-600 mb-5 mt-2 transition-all duration-300 hover:scale-110 hover:bg-brand-100"
         style="margin-bottom: 1.5rem; margin-top: 0.5rem;">
        <?= ui_icon($icon, 'h-7 w-7') ?>
    </div>
    
    <h3 class="text-base font-bold text-gray-900"><?= esc(lang($title)) ?></h3>
    <p class="mt-2 text-sm text-gray-500 max-w-md leading-relaxed" style="margin-top: 0.75rem;"><?= esc(lang($description)) ?></p>
    
    <?php if ($actionUrl && $actionLabel): ?>
        <!-- Button container with robust spacing to prevent cramped layouts -->
        <div class="mt-6" style="margin-top: 2rem; margin-bottom: 0.5rem;">
            <a href="<?= esc($actionUrl) ?>" class="<?= esc(action_button_class('primary')) ?> px-5 py-2.5 text-sm font-semibold inline-flex items-center gap-2">
                <?= ui_icon('plus', 'h-4 w-4') ?>
                <?= esc(lang($actionLabel)) ?>
            </a>
        </div>
    <?php endif; ?>
</div>
