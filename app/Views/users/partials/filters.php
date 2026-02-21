<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('Users.searchPlaceholder') ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Users.status') ?></label>
        <select name="status" class="<?= esc(filter_input_class()) ?>">
            <option value=""><?= lang('Users.allStatuses') ?></option>
            <?php $status = (string) request()->getGet('status'); ?>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= lang('App.active') ?></option>
            <option value="pending_approval" <?= $status === 'pending_approval' ? 'selected' : '' ?>><?= lang('Users.pendingApproval') ?></option>
        </select>
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Users.role') ?></label>
        <select name="role" class="<?= esc(filter_input_class()) ?>">
            <option value=""><?= lang('Users.allRoles') ?></option>
            <?php $role = (string) request()->getGet('role'); ?>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>><?= lang('Users.adminRole') ?></option>
            <option value="user" <?= $role === 'user' ? 'selected' : '' ?>><?= lang('Users.userRole') ?></option>
        </select>
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
