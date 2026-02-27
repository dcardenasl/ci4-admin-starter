<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Metrics.dateFrom') ?></label>
        <input type="date" name="dateFrom" value="<?= esc((string) ($filters['dateFrom'] ?? $filters['date_from'] ?? '')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Metrics.dateTo') ?></label>
        <input type="date" name="dateTo" value="<?= esc((string) ($filters['dateTo'] ?? $filters['date_to'] ?? '')) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Metrics.groupBy') ?></label>
        <select name="groupBy" class="<?= esc(filter_input_class()) ?>">
            <option value="day" <?= ($filters['groupBy'] ?? $filters['group_by'] ?? 'day') === 'day' ? 'selected' : '' ?>><?= lang('Metrics.byDay') ?></option>
            <option value="week" <?= ($filters['groupBy'] ?? $filters['group_by'] ?? '') === 'week' ? 'selected' : '' ?>><?= lang('Metrics.byWeek') ?></option>
            <option value="month" <?= ($filters['groupBy'] ?? $filters['group_by'] ?? '') === 'month' ? 'selected' : '' ?>><?= lang('Metrics.byMonth') ?></option>
        </select>
    </div>
</div>
