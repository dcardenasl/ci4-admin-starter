<?php $application = $application ?? []; ?>
<div class="mb-4">
    <a href="<?= route_to('admin.iam.applications') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Iam.applications_title') ?></a>
</div>

<?php if (! empty($error)): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-red-600"><?= esc($error) ?></p>
    </div>
<?php elseif (! empty($application)): ?>
    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('Iam.applications_details') ?></h3>
        </div>

        <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_code') ?></dt>
                <dd class="mt-1 text-gray-900"><code class="text-xs"><?= esc((string) ($application['code'] ?? '-')) ?></code></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_name') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($application['name'] ?? '-')) ?></dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-gray-500"><?= lang('Iam.field_description') ?></dt>
                <dd class="mt-1 text-gray-900 whitespace-pre-line"><?= esc((string) ($application['description'] ?? '-')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_active') ?></dt>
                <dd class="mt-1 text-gray-900"><?= ! empty($application['is_active']) ? '✓' : '—' ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('TableColumns.created_at') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($application['created_at'] ?? '-')) ?></dd>
            </div>
        </dl>

        <p class="mt-6 text-xs text-gray-500"><?= esc(lang('Iam.applications_managed_server_side')) ?></p>
    </section>
<?php endif; ?>
