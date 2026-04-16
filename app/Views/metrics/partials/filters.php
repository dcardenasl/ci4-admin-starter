<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
    <div>
        <label class="<?= esc(filter_label_class()) ?>">Period</label>
        <select name="period" class="<?= esc(filter_input_class()) ?>">
            <?php $period = (string) ($filters['period'] ?? '24h'); ?>
            <?php foreach (($periodOptions ?? []) as $option): ?>
                <?php
                $value = (string) ($option['value'] ?? '');
                if ($value === '') {
                    continue;
                }
                $label = (string) ($option['label'] ?? $value);
                ?>
                <option value="<?= esc($value) ?>" <?= $period === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
