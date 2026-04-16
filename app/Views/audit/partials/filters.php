<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('Audit.search_placeholder') ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Audit.action') ?></label>
        <select name="action" class="<?= esc(filter_input_class()) ?>">
            <option value=""><?= lang('Audit.all_actions') ?></option>
            <?php $action = (string) request()->getGet('action'); ?>
            <?php foreach (($actionOptions ?? []) as $option): ?>
                <?php
                $value = (string) ($option['value'] ?? '');
                if ($value === '') {
                    continue;
                }
                $label = (string) ($option['label'] ?? $value);
                ?>
                <option value="<?= esc($value) ?>" <?= $action === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Audit.user_id') ?></label>
        <input type="text" name="user_id" value="<?= esc((string) request()->getGet('user_id')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <?= view('layouts/partials/filter_limit', ['limitOptions' => $limitOptions ?? [10, 25, 50, 100]]) ?>
</div>
