<?php
/**
 * Consistent admin page header for resource create/edit/show screens.
 *
 * @var string|null $backUrl
 * @var string|null $backLabel Translation key or plain text.
 * @var string|null $eyebrow Translation key or plain text.
 * @var string $title Translation key or plain text.
 * @var string|null $subtitle Optional plain text or HTML.
 * @var bool|null $subtitleIsHtml
 * @var string|null $badge Optional rendered badge HTML.
 * @var string|null $actions Optional rendered actions HTML.
 */

$backUrl        = $backUrl        ?? null;
$backLabel      = $backLabel      ?? 'App.back';
$eyebrow        = $eyebrow        ?? null;
$title          = $title          ?? '';
$subtitle       = $subtitle       ?? null;
$subtitleIsHtml = $subtitleIsHtml ?? false;
$badge          = $badge          ?? null;
$actions        = $actions        ?? null;

$renderLabel = static fn (string $value): string => lang($value) !== $value ? lang($value) : $value;
?>
<div class="mb-5 space-y-4">
    <?php if ($backUrl): ?>
        <a href="<?= esc($backUrl) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
            <span aria-hidden="true">&larr;</span>
            <span><?= esc($renderLabel($backLabel)) ?></span>
        </a>
    <?php endif; ?>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <?php if ($eyebrow): ?>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400"><?= esc($renderLabel($eyebrow)) ?></p>
            <?php endif; ?>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-bold text-gray-900"><?= esc($renderLabel($title)) ?></h2>
                <?php if ($badge): ?>
                    <?= $badge ?>
                <?php endif; ?>
            </div>
            <?php if ($subtitle): ?>
                <div class="mt-1 text-sm text-gray-500">
                    <?php if ($subtitleIsHtml): ?>
                        <?= $subtitle ?>
                    <?php else: ?>
                        <?= esc($subtitle) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($actions): ?>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <?= $actions ?>
            </div>
        <?php endif; ?>
    </div>
</div>
