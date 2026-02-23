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
            <option value="create" <?= $action === 'create' ? 'selected' : '' ?>><?= lang('Audit.actionCreate') ?></option>
            <option value="update" <?= $action === 'update' ? 'selected' : '' ?>><?= lang('Audit.actionUpdate') ?></option>
            <option value="delete" <?= $action === 'delete' ? 'selected' : '' ?>><?= lang('Audit.actionDelete') ?></option>
            <option value="login" <?= $action === 'login' ? 'selected' : '' ?>><?= lang('Audit.actionLogin') ?></option>
            <option value="logout" <?= $action === 'logout' ? 'selected' : '' ?>><?= lang('Audit.actionLogout') ?></option>
        </select>
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Audit.userId') ?></label>
        <input type="text" name="user_id" value="<?= esc((string) request()->getGet('user_id')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <?= view('layouts/partials/filter_limit') ?>
</div>
