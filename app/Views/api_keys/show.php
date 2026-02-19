<div class="mb-4">
    <a href="<?= site_url('admin/api-keys') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('ApiKeys.backToList') ?></a>
</div>

<?php if (! empty($error)): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-red-600"><?= esc($error) ?></p>
    </div>
<?php elseif (! empty($apiKey)): ?>
    <?php
    $id = (string) ($apiKey['id'] ?? '');
    $generatedApiKey = (string) (session('generatedApiKey') ?? '');
    $generatedApiKeyName = (string) (session('generatedApiKeyName') ?? ($apiKey['name'] ?? ''));
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900"><?= lang('ApiKeys.details') ?></h3>
                <div class="flex items-center gap-2">
                    <a href="<?= site_url('admin/api-keys/' . esc($id, 'url') . '/edit') ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.edit') ?></a>
                    <form method="post" action="<?= site_url('admin/api-keys/' . esc($id, 'url') . '/delete') ?>" onsubmit="return confirm('<?= lang('ApiKeys.confirmDelete') ?>');">
                        <?= csrf_field() ?>
                        <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"><?= lang('App.delete') ?></button>
                    </form>
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-gray-500"><?= lang('ApiKeys.name') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($apiKey['name'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('ApiKeys.keyPrefix') ?></dt>
                    <dd class="mt-1 text-gray-900 font-mono text-xs"><?= esc((string) ($apiKey['key_prefix'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('ApiKeys.status') ?></dt>
                    <dd class="mt-1">
                        <?php $isActive = ! empty($apiKey['is_active']); ?>
                        <span class="inline-flex rounded-full px-2 py-1 text-xs <?= $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' ?>">
                            <?= $isActive ? lang('ApiKeys.active') : lang('ApiKeys.inactive') ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('ApiKeys.rateLimitRequests') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($apiKey['rate_limit_requests'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('ApiKeys.rateLimitWindow') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($apiKey['rate_limit_window'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('ApiKeys.userRateLimit') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($apiKey['user_rate_limit'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('ApiKeys.ipRateLimit') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($apiKey['ip_rate_limit'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('ApiKeys.createdAt') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc(format_date($apiKey['created_at'] ?? null)) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('ApiKeys.updatedAt') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc(format_date($apiKey['updated_at'] ?? null)) ?></dd>
                </div>
            </dl>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('ApiKeys.quickActions') ?></h3>
            <div class="mt-4 space-y-3">
                <a href="<?= site_url('admin/api-keys/' . esc($id, 'url') . '/edit') ?>" class="block w-full text-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.edit') ?></a>
                <a href="<?= site_url('admin/api-keys/create') ?>" class="block w-full text-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('ApiKeys.create') ?></a>
            </div>
        </section>
    </div>

    <?php if ($generatedApiKey !== ''): ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            x-data="{ copied: false, revealed: false, key: '<?= esc($generatedApiKey, 'js') ?>' }">
            <div class="w-full max-w-2xl rounded-xl border border-gray-200 bg-white p-5 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900"><?= lang('ApiKeys.rawKeyOneTimeTitle') ?></h3>
                <p class="mt-2 text-sm text-gray-600"><?= lang('ApiKeys.rawKeyOneTimeBody') ?></p>
                <p class="mt-1 text-sm text-gray-500"><?= esc($generatedApiKeyName) ?></p>

                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <label class="text-xs font-medium uppercase tracking-wide text-amber-700"><?= lang('ApiKeys.rawKey') ?></label>
                    <div class="mt-2 flex items-center gap-2">
                        <code class="flex-1 overflow-auto rounded-md bg-white px-3 py-2 text-xs text-gray-900" x-text="revealed ? key : '*****************************'"></code>
                        <button type="button" class="rounded-md border border-gray-300 px-3 py-2 text-xs text-gray-700 hover:bg-gray-50" @click="revealed = !revealed" x-text="revealed ? '<?= esc(lang('ApiKeys.hideKey')) ?>' : '<?= esc(lang('ApiKeys.showKey')) ?>'"></button>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="navigator.clipboard.writeText(key).then(() => { copied = true; setTimeout(() => copied = false, 2000); })" x-text="copied ? '<?= esc(lang('ApiKeys.copied')) ?>' : '<?= esc(lang('ApiKeys.copyKey')) ?>'"></button>
                    <a href="<?= site_url('admin/api-keys/' . esc($id, 'url')) ?>" class="rounded-lg bg-brand-600 px-3 py-2 text-sm text-white hover:bg-brand-700"><?= lang('App.close') ?></a>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
