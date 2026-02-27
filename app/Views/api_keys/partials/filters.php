<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('ApiKeys.searchPlaceholder') ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('ApiKeys.name') ?></label>
        <input type="text" name="name" value="<?= esc((string) request()->getGet('name')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('ApiKeys.status') ?></label>
        <?php $active = (string) (request()->getGet('isActive') ?? request()->getGet('is_active')); ?>
        <select name="isActive" class="<?= esc(filter_input_class()) ?>">
            <option value=""><?= lang('ApiKeys.allStatuses') ?></option>
            <option value="1" <?= $active === '1' ? 'selected' : '' ?>><?= lang('ApiKeys.active') ?></option>
            <option value="0" <?= $active === '0' ? 'selected' : '' ?>><?= lang('ApiKeys.inactive') ?></option>
        </select>
    </div>
    <?= view('layouts/partials/filter_limit') ?>
</div>
