<?php if (empty($recent_activity)): ?>
    <p class="text-sm text-gray-500 text-center py-4 italic"><?= lang('Dashboard.noRecentActivity') ?></p>
<?php else: ?>
    <ul role="list" class="-mb-8">
        <?php foreach ($recent_activity as $index => $item): ?>
            <?= view('dashboard/partials/activity_item', [
                'item'   => $item,
                'isLast' => $index === count($recent_activity) - 1,
            ]) ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
