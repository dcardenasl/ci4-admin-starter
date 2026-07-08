<?php
/**
 * Standard right-column metadata panel.
 *
 * @var string|null $title Translation key or plain text.
 * @var string|null $description Translation key or plain text.
 * @var array<int, array{label:string,value:mixed,isHtml?:bool}> $items
 * @var string|null $content Optional custom rendered metadata HTML.
 */

$title       = $title       ?? 'App.details';
$description = $description ?? null;
$items       = $items       ?? [];
$content     = $content     ?? null;

ob_start();
?>
<?php if ($content !== null): ?>
    <?= $content ?>
<?php else: ?>
    <dl class="divide-y divide-gray-100 text-sm">
        <?php foreach ($items as $item): ?>
            <?php
                $label  = (string) ($item['label'] ?? '');
            $value  = $item['value'] ?? '—';
            $isHtml = (bool) ($item['isHtml'] ?? false);
            ?>
            <div class="py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400"><?= esc(lang($label)) ?></dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">
                    <?php if ($isHtml): ?>
                        <?= $value ?>
                    <?php else: ?>
                        <?= esc($value ?? '—') ?>
                    <?php endif; ?>
                </dd>
            </div>
        <?php endforeach; ?>
    </dl>
<?php endif; ?>
<?php $panelContent = ob_get_clean(); ?>

<?= view('components/display/form_section', [
    'title' => $title,
    'description' => $description,
    'badge' => null,
    'content' => $panelContent,
]) ?>
