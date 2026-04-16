<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('Users.search_placeholder') ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Users.status') ?></label>
        <select name="status" class="<?= esc(filter_input_class()) ?>">
            <option value=""><?= lang('Users.all_statuses') ?></option>
            <?php $status = (string) request()->getGet('status'); ?>
            <?php foreach (($statusOptions ?? []) as $option): ?>
                <?php
                $value = (string) ($option['value'] ?? '');
                if ($value === '') {
                    continue;
                }
                $label = (string) ($option['label'] ?? $value);
                ?>
                <option value="<?= esc($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Users.role') ?></label>
        <select name="role" class="<?= esc(filter_input_class()) ?>">
            <option value=""><?= lang('Users.all_roles') ?></option>
            <?php $role = (string) request()->getGet('role'); ?>
            <?php foreach (($roleOptions ?? []) as $option): ?>
                <?php
                $value = (string) ($option['value'] ?? '');
                if ($value === '') {
                    continue;
                }
                $label = (string) ($option['label'] ?? $value);
                ?>
                <option value="<?= esc($value) ?>" <?= $role === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?= view('layouts/partials/filter_limit', ['limitOptions' => $limitOptions ?? [10, 25, 50, 100]]) ?>
</div>
