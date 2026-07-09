<?php
/**
 * Standard right-column action panel.
 *
 * @var string|null $title Translation key or plain text.
 * @var string|null $description Translation key or plain text.
 * @var string $content Rendered action controls.
 * @var string|null $dangerContent Rendered destructive controls.
 */

$title         = $title         ?? 'App.actions';
$description   = $description   ?? null;
$content       = $content       ?? '';
$dangerContent = $dangerContent ?? '';
?>
<?php ob_start(); ?>
<div class="space-y-3">
    <?= $content ?>

    <?php if (trim((string) $dangerContent) !== ''): ?>
        <div class="border-t border-gray-100 pt-3">
            <?= $dangerContent ?>
        </div>
    <?php endif; ?>
</div>
<?php $panelContent = ob_get_clean(); ?>

<?= view('components/display/form_section', [
    'title' => $title,
    'description' => $description,
    'badge' => null,
    'content' => $panelContent,
    'bodyClass' => 'space-y-3',
]) ?>
