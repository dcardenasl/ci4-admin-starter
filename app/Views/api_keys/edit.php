<?php $id = (string) ($apiKey['id'] ?? ''); ?>

<div class="mb-4">
    <a href="<?= site_url('admin/api-keys/' . esc($id, 'url')) ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('ApiKeys.backToDetails') ?></a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= lang('ApiKeys.edit') ?></h3>

    <form method="post" action="<?= site_url('admin/api-keys/' . esc($id, 'url')) ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="name"><?= lang('ApiKeys.name') ?></label>
            <input id="name" name="name" type="text" value="<?= esc(old('name', $apiKey['name'] ?? '')) ?>"
                class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
            <?= render_field_error('name') ?>
        </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700" for="isActive"><?= lang('ApiKeys.status') ?></label>
                    <?php $currentActive = old('isActive', isset($apiKey['isActive']) ? ((int) ((bool) $apiKey['isActive'])) : 1); ?>
                    <select id="isActive" name="isActive"
                        class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('isActive') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
                        <option value="1" <?= (string) $currentActive === '1' ? 'selected' : '' ?>><?= lang('ApiKeys.active') ?></option>
                        <option value="0" <?= (string) $currentActive === '0' ? 'selected' : '' ?>><?= lang('ApiKeys.inactive') ?></option>
                    </select>
                    <?= render_field_error('isActive') ?>
                </div>
        
                <?php $fieldMapping = [
                    'rateLimitRequests' => $apiKey['rateLimitRequests'] ?? '',
                    'rateLimitWindow'   => $apiKey['rateLimitWindow'] ?? '',
                    'userRateLimit'     => $apiKey['userRateLimit'] ?? '',
                    'ipRateLimit'       => $apiKey['ipRateLimit'] ?? '',
                ];
$labels = [
    'rateLimitRequests' => lang('ApiKeys.rateLimitRequests'),
    'rateLimitWindow'   => lang('ApiKeys.rateLimitWindow'),
    'userRateLimit'     => lang('ApiKeys.userRateLimit'),
    'ipRateLimit'       => lang('ApiKeys.ipRateLimit'),
]; ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($labels as $field => $label): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="<?= esc($field) ?>"><?= esc($label) ?></label>
                            <input id="<?= esc($field) ?>" name="<?= esc($field) ?>" type="number" min="1" value="<?= esc(old($field, $fieldMapping[$field])) ?>"
                                class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error($field) ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
                            <?= render_field_error($field) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700"><?= lang('App.save') ?></button>
            <a href="<?= site_url('admin/api-keys/' . esc($id, 'url')) ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.cancel') ?></a>
        </div>
    </form>
</section>
