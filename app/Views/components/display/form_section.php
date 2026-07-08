<?php
/**
 * Reusable form section wrapper for dense admin forms.
 *
 * @var string $title Translation key or plain text for the section title.
 * @var string|null $description Optional translation key or plain text for helper copy.
 * @var string|null $badge Optional translation key or plain text for a right-aligned badge.
 * @var string|null $content Inner HTML for the section body.
 * @var string|null $outerClass Extra classes for the outer section element.
 * @var string|null $bodyClass Extra classes for the section body container.
 */

$title       = $title ?? '';
$description = $description ?? null;
$badge       = $badge ?? null;
$content     = $content ?? '';
$outerClass  = $outerClass ?? '';
$bodyClass   = $bodyClass ?? '';
?>
<section class="rounded-xl border border-gray-200 bg-white shadow-sm<?= $outerClass !== '' ? ' ' . esc($outerClass) : '' ?>">
    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700">
                <?= esc(lang($title)) ?>
            </h3>
            <?php if ($description): ?>
                <p class="mt-1 text-sm text-gray-500"><?= esc(lang($description)) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($badge): ?>
            <span class="inline-flex shrink-0 items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
                <?= esc(lang($badge)) ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="p-5<?= $bodyClass !== '' ? ' ' . esc($bodyClass) : '' ?>">
        <?= $content ?>
    </div>
</section>
