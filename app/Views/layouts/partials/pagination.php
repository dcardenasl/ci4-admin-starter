<?php if (! isset($pager)): ?>
    <?php return; ?>
<?php endif; ?>

<?php $links = $pager->links(); ?>
<?php if ($links === ''): ?>
    <?php return; ?>
<?php endif; ?>

<div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4 text-sm text-gray-600">
    <span>Resultados paginados</span>
    <?= $links ?>
</div>
