<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('Audit.searchPlaceholder') ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Audit.action') ?></label>
        <select name="action" class="<?= esc(filter_input_class()) ?>">
            <option value=""><?= lang('Audit.allActions') ?></option>
            <?php $action = (string) request()->getGet('action'); ?>
            <option value="create" <?= $action === 'create' ? 'selected' : '' ?>>Create</option>
            <option value="update" <?= $action === 'update' ? 'selected' : '' ?>>Update</option>
            <option value="delete" <?= $action === 'delete' ? 'selected' : '' ?>>Delete</option>
            <option value="login" <?= $action === 'login' ? 'selected' : '' ?>>Login</option>
            <option value="logout" <?= $action === 'logout' ? 'selected' : '' ?>>Logout</option>
        </select>
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>">User ID</label>
        <input type="text" name="user_id" value="<?= esc((string) request()->getGet('user_id')) ?>" class="<?= esc(filter_input_class()) ?>">
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
