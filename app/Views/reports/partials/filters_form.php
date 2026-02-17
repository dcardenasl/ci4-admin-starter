<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Reports.reportType') ?></label>
        <select name="report_type" class="<?= esc(filter_input_class()) ?>">
            <option value="users" <?= ($filters['report_type'] ?? 'users') === 'users' ? 'selected' : '' ?>><?= lang('Reports.usersReport') ?></option>
            <option value="activity" <?= ($filters['report_type'] ?? '') === 'activity' ? 'selected' : '' ?>><?= lang('Reports.activityReport') ?></option>
            <option value="files" <?= ($filters['report_type'] ?? '') === 'files' ? 'selected' : '' ?>><?= lang('Reports.filesReport') ?></option>
        </select>
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Reports.dateFrom') ?></label>
        <input type="date" name="date_from" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Reports.dateTo') ?></label>
        <input type="date" name="date_to" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Reports.groupBy') ?></label>
        <select name="group_by" class="<?= esc(filter_input_class()) ?>">
            <option value="day" <?= ($filters['group_by'] ?? 'day') === 'day' ? 'selected' : '' ?>><?= lang('Reports.byDay') ?></option>
            <option value="week" <?= ($filters['group_by'] ?? '') === 'week' ? 'selected' : '' ?>><?= lang('Reports.byWeek') ?></option>
            <option value="month" <?= ($filters['group_by'] ?? '') === 'month' ? 'selected' : '' ?>><?= lang('Reports.byMonth') ?></option>
        </select>
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Reports.perPage') ?></label>
        <input type="number" min="1" max="100" name="limit" value="<?= esc((string) ($filters['limit'] ?? 25)) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) ($filters['search'] ?? '')) ?>" placeholder="<?= lang('Reports.searchPlaceholder') ?>" class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Reports.status') ?></label>
        <input type="text" name="status" value="<?= esc((string) ($filters['status'] ?? '')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Reports.role') ?></label>
        <input type="text" name="role" value="<?= esc((string) ($filters['role'] ?? '')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Reports.action') ?></label>
        <input type="text" name="action" value="<?= esc((string) ($filters['action'] ?? '')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
</div>
