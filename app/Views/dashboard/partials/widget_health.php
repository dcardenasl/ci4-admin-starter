<?php foreach ($healthServices as $svc): ?>
<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider"><?= lang('Dashboard.system_status') ?></h3>
        <span class="text-xs font-medium text-gray-500"><?= esc($svc['name']) ?></span>
    </div>
    <?= view('dashboard/partials/health_card', ['health' => $svc['health']]) ?>
</section>
<?php endforeach; ?>
