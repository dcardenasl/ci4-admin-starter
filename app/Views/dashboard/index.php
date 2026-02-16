<section class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500">Archivos recientes</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['files'] ?? 0)) ?></p>
    </article>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500">Rol</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['role'] ?? 'user')) ?></p>
    </article>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500">Email verificado</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['emailVerified'] ?? 'No')) ?></p>
    </article>
</section>

<section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Acciones rapidas</h3>
        <div class="space-x-2">
            <a href="<?= site_url('files') ?>" class="inline-block rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700">Gestionar archivos</a>
            <a href="<?= site_url('profile') ?>" class="inline-block rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Editar perfil</a>
        </div>
    </div>

    <div class="mt-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Ultimos archivos</h4>
        <?php if (empty($recentFiles)): ?>
            <p class="text-sm text-gray-500">No hay archivos recientes.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-500">
                        <tr>
                            <th class="py-2 pr-4">Nombre</th>
                            <th class="py-2 pr-4">Estado</th>
                            <th class="py-2 pr-4">Fecha</th>
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
