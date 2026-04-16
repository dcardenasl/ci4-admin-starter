<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('ApiKeys.search_placeholder') ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('ApiKeys.name') ?></label>
        <input type="text" name="name" value="<?= esc((string) request()->getGet('name')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('ApiKeys.status') ?></label>
        <?php $active = (string) (request()->getGet('is_active') ?? ''); ?>
        <select name="is_active" class="<?= esc(filter_input_class()) ?>">
            <option value=""><?= lang('ApiKeys.all_statuses') ?></option>
            <?php foreach (($statusOptions ?? []) as $option): ?>
                <?php
                $value = (string) ($option['value'] ?? '');
                if ($value === '') {
                    continue;
                }
                $label = (string) ($option['label'] ?? $value);
                ?>
                <option value="<?= esc($value) ?>" <?= $active === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?= view('layouts/partials/filter_limit', ['limitOptions' => $limitOptions ?? [10, 25, 50, 100]]) ?>
</div>
