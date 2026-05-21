<?php foreach ($stats as $stat): ?>
    <?= view('dashboard/partials/stat_card', [
        'label'  => $stat['label'],
        'value'  => $stat['value'],
        'icon'   => $stat['icon'],
        'suffix' => $stat['suffix'] ?? null,
    ]) ?>
<?php endforeach; ?>
