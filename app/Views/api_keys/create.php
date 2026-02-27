<div class="mb-4">
    <a href="<?= site_url('admin/api-keys') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('ApiKeys.backToList') ?></a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= lang('ApiKeys.create') ?></h3>

    <form method="post" action="<?= site_url('admin/api-keys') ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="name"><?= lang('ApiKeys.name') ?></label>
            <input id="name" name="name" type="text" value="<?= esc(old('name', '')) ?>" required
                class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
            <?= render_field_error('name') ?>
        </div>

        <?php $labels = [
            'rateLimitRequests' => lang('ApiKeys.rateLimitRequests'),
            'rateLimitWindow'   => lang('ApiKeys.rateLimitWindow'),
            'userRateLimit'     => lang('ApiKeys.userRateLimit'),
            'ipRateLimit'       => lang('ApiKeys.ipRateLimit'),
        ]; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($labels as $field => $label): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="<?= esc($field) ?>"><?= esc($label) ?></label>
                    <input id="<?= esc($field) ?>" name="<?= esc($field) ?>" type="number" min="1" value="<?= esc(old($field, '')) ?>"
                        class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error($field) ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
                    <?= render_field_error($field) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700"><?= lang('ApiKeys.create') ?></button>
            <a href="<?= site_url('admin/api-keys') ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.cancel') ?></a>
        </div>
    </form>
</section>
