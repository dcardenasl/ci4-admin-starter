<?php
/**
 * Health card body partial.
 *
 * Expected variables:
 *   $health  array — result from HealthApiService::check()
 *   $name    string — display name of the upstream service
 */

/** @var array<string, mixed> $health */
/** @var string $name */

$healthTone    = health_tone_badge($health['state'] ?? 'down');
$healthData    = is_array($health['data'] ?? null) ? $health['data'] : [];
$healthChecks  = is_array($healthData['checks'] ?? null) ? $healthData['checks'] : [];
$healthTimestamp = $healthData['timestamp'] ?? null;
$dbCheck       = is_array($healthChecks['database'] ?? null) ? $healthChecks['database'] : null;
$diskCheck     = is_array($healthChecks['disk'] ?? null) ? $healthChecks['disk'] : null;
$writableCheck = is_array($healthChecks['writable'] ?? null) ? $healthChecks['writable'] : null;
?>

<!-- Estado general -->
<div class="flex items-center gap-3 p-3 rounded-lg <?= esc($healthTone['bg']) ?>">
    <span class="flex h-3 w-3 relative">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?= esc($healthTone['dot']) ?> opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 <?= esc($healthTone['dot']) ?>"></span>
    </span>
    <span class="text-sm font-medium <?= esc($healthTone['text']) ?>">
        <?= esc(lang('Dashboard.status_' . ($health['state'] ?? 'down'))) ?>
        (<?= esc((string) ($health['latency_ms'] ?? 0)) ?>ms)
    </span>
</div>

<?php if ($healthTimestamp !== null): ?>
    <p class="mt-2 text-xs text-gray-500">
        <?= esc(lang('Dashboard.last_check')) ?>: <?= esc((string) $healthTimestamp) ?>
    </p>
<?php endif; ?>

<!-- Detalle por componente -->
<?php if ($dbCheck !== null || $diskCheck !== null || $writableCheck !== null): ?>
    <div class="mt-4 space-y-2 border-t border-gray-100 pt-4">

        <?php if ($dbCheck !== null): ?>
            <?php $tone = check_tone_badge((string) ($dbCheck['status'] ?? 'unknown')); ?>
            <div class="flex items-center justify-between gap-3 py-1">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <?= ui_icon('database', 'h-4 w-4 text-gray-400') ?>
                    <span><?= esc(lang('Dashboard.check_database')) ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full <?= esc($tone['dot']) ?>"></span>
                    <span class="text-xs font-medium <?= esc($tone['text']) ?>">
                        <?php if (isset($dbCheck['response_time_ms']) && is_numeric($dbCheck['response_time_ms'])): ?>
                            <?= esc((string) $dbCheck['response_time_ms']) ?> ms
                        <?php else: ?>
                            <?= esc(lang('Dashboard.check_status_' . ($dbCheck['status'] ?? 'unknown'))) ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($diskCheck !== null): ?>
            <?php
            $tone = check_tone_badge((string) ($diskCheck['status'] ?? 'unknown'));
            $usedPct = $diskCheck['used_percentage'] ?? null;
            $freeMb  = $diskCheck['free_space_mb'] ?? null;
            $freeLabel = '';
            if (is_numeric($freeMb)) {
                $freeLabel = (float) $freeMb >= 1024
                    ? number_format((float) $freeMb / 1024, 1) . ' GB'
                    : number_format((float) $freeMb, 0) . ' MB';
            }
            ?>
            <div class="py-1">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <?= ui_icon('hard-drive', 'h-4 w-4 text-gray-400') ?>
                        <span><?= esc(lang('Dashboard.check_disk')) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full <?= esc($tone['dot']) ?>"></span>
                        <span class="text-xs font-medium <?= esc($tone['text']) ?>">
                            <?php if (is_numeric($usedPct)): ?>
                                <?= esc(number_format((float) $usedPct, 0)) ?>%<?php if ($freeLabel !== ''): ?> · <?= esc($freeLabel) ?> <?= esc(lang('Dashboard.disk_free_suffix')) ?><?php endif; ?>
                            <?php else: ?>
                                <?= esc(lang('Dashboard.check_status_' . ($diskCheck['status'] ?? 'unknown'))) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <?php if (is_numeric($usedPct)): ?>
                    <div class="mt-1.5 ml-6 h-1 w-[calc(100%-1.5rem)] rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full <?= esc($tone['dot']) ?>" style="width: <?= esc((string) min(100, max(0, (float) $usedPct))) ?>%"></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($writableCheck !== null): ?>
            <?php
            $tone = check_tone_badge((string) ($writableCheck['status'] ?? 'unknown'));
            $nonWritable  = $writableCheck['non_writable'] ?? [];
            $blockedCount = is_array($nonWritable) ? count($nonWritable) : 0;
            ?>
            <div class="py-1">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <?= ui_icon('folder-lock', 'h-4 w-4 text-gray-400') ?>
                        <span><?= esc(lang('Dashboard.check_writable')) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full <?= esc($tone['dot']) ?>"></span>
                        <span class="text-xs font-medium <?= esc($tone['text']) ?>">
                            <?php if ($blockedCount === 0): ?>
                                <?= esc(lang('Dashboard.writable_ok')) ?>
                            <?php else: ?>
                                <?= esc(sprintf(lang('Dashboard.writable_blocked'), $blockedCount)) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <?php if ($blockedCount > 0 && is_array($nonWritable)): ?>
                    <ul class="mt-1 ml-6 space-y-0.5 text-xs text-red-600 font-mono break-all">
                        <?php foreach ($nonWritable as $path): ?>
                            <li><?= esc((string) $path) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
