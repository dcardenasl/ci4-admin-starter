<section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Dashboard.recentFiles') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['files'] ?? 0)) ?></p>
    </article>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Dashboard.role') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['role'] ?? 'user')) ?></p>
    </article>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Dashboard.emailVerified') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['emailVerified'] ?? lang('App.no'))) ?></p>
    </article>
    <?php
    $healthState = $apiHealth['state'] ?? 'down';
    $healthTone = match ($healthState) {
        'up' => [
            'dot' => 'bg-green-500',
            'chip' => 'bg-green-100 text-green-800',
            'panel' => 'bg-green-50 border-green-200',
        ],
        'degraded' => [
            'dot' => 'bg-amber-500',
            'chip' => 'bg-amber-100 text-amber-800',
            'panel' => 'bg-amber-50 border-amber-200',
        ],
        default => [
            'dot' => 'bg-red-500',
            'chip' => 'bg-red-100 text-red-800',
            'panel' => 'bg-red-50 border-red-200',
        ],
    };
    ?>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500"><?= lang('Dashboard.apiHealth') ?></p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full <?= esc($healthTone['dot']) ?>"></span>
                    <p class="text-xl font-semibold text-gray-900"><?= esc((string) ($stats['apiHealth'] ?? lang('Dashboard.down'))) ?></p>
                </div>
            </div>
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= esc($healthTone['chip']) ?>">
                HTTP <?= esc((string) (($apiHealth['status'] ?? 0) > 0 ? $apiHealth['status'] : '-')) ?>
            </span>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-gray-600">
            <div class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-2">
                <p class="text-[11px] uppercase tracking-wide text-gray-500">Endpoint</p>
                <p class="mt-0.5 font-medium text-gray-700"><?= esc((string) ($apiHealth['path'] ?? '/health')) ?></p>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-2">
                <p class="text-[11px] uppercase tracking-wide text-gray-500">Latency</p>
                <p class="mt-0.5 font-medium text-gray-700"><?= esc((string) (($apiHealth['latency_ms'] ?? 0) > 0 ? $apiHealth['latency_ms'] . ' ms' : '-')) ?></p>
            </div>
        </div>

        <?php if (! empty($apiHealth['message'])): ?>
            <div class="mt-3 rounded-md border px-2.5 py-2 text-xs <?= esc($healthTone['panel']) ?>">
                <?= esc((string) $apiHealth['message']) ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Dashboard.quickActions') ?></h3>
        <div class="space-x-2">
            <a href="<?= site_url('files') ?>" class="inline-block rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Dashboard.manageFiles') ?></a>
            <a href="<?= site_url('profile') ?>" class="inline-block rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Dashboard.editProfile') ?></a>
        </div>
    </div>

    <div class="mt-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-3"><?= lang('Dashboard.latestFiles') ?></h4>
        <?php if (empty($recentFiles)): ?>
            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                <p class="text-sm text-gray-600"><?= lang('Dashboard.noRecentFiles') ?></p>
                <a href="<?= site_url('files') ?>" class="mt-3 inline-flex rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Dashboard.manageFiles') ?></a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-500">
                        <tr>
                            <th class="py-2 pr-4"><?= lang('Dashboard.fileName') ?></th>
                            <th class="py-2 pr-4"><?= lang('Dashboard.status') ?></th>
                            <th class="py-2 pr-4"><?= lang('Dashboard.date') ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($recentFiles as $file): ?>
                            <tr>
                                <td class="py-2 pr-4 text-gray-800"><?= esc((string) ($file['name'] ?? $file['filename'] ?? '-')) ?></td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs <?= status_badge($file['status'] ?? 'active') ?>">
                                        <?= esc((string) ($file['status'] ?? 'active')) ?>
                                    </span>
                                </td>
                                <td class="py-2 pr-4 text-gray-600"><?= esc(format_date($file['created_at'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
