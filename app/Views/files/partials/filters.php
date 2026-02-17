<div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('Files.searchPlaceholder') ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.perPage') ?></label>
        <?php $limit = (string) (request()->getGet('limit') ?: '25'); ?>
        <select name="limit" class="<?= esc(filter_input_class()) ?>">
            <option value="10" <?= $limit === '10' ? 'selected' : '' ?>>10</option>
            <option value="25" <?= $limit === '25' ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $limit === '50' ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $limit === '100' ? 'selected' : '' ?>>100</option>
        </select>
    </div>
</div>
