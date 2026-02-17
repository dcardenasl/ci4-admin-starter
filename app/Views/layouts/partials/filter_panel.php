<?php
$actionUrl ??= current_url();
$clearUrl ??= $actionUrl;
$method ??= 'get';
$title ??= lang('App.filters');
$submitLabel ??= lang('App.search');
$submitFullWidth ??= false;
$hasFilters ??= has_active_filters();
$fieldsView ??= null;
$fieldsData ??= [];
$reactiveHasFilters ??= false;
$filterDefaults ??= [];
$ignoredFilterKeys ??= ['sort', 'page', 'cursor'];

$normalizedDefaults = [];
if (is_array($filterDefaults)) {
    foreach ($filterDefaults as $key => $value) {
        if (! is_string($key) || $key === '') {
            continue;
        }
        if (! is_scalar($value) && $value !== null) {
            continue;
        }

        $normalizedDefaults[$key] = trim((string) $value);
    }
}

$normalizedIgnoredKeys = [];
if (is_array($ignoredFilterKeys)) {
    foreach ($ignoredFilterKeys as $key) {
        if (is_string($key) && $key !== '') {
            $normalizedIgnoredKeys[] = $key;
        }
    }
}

$defaultsJson = json_encode($normalizedDefaults);
if (! is_string($defaultsJson) || $defaultsJson === '') {
    $defaultsJson = '{}';
}

$ignoredJson = json_encode($normalizedIgnoredKeys);
if (! is_string($ignoredJson) || $ignoredJson === '') {
    $ignoredJson = '[]';
}
?>
<form
    method="<?= esc($method) ?>"
    action="<?= esc($actionUrl) ?>"
    class="<?= esc(filter_panel_class()) ?>"
    data-table-filter-form="1"
    data-reactive-has-filters="<?= $reactiveHasFilters ? '1' : '0' ?>"
    data-filter-defaults="<?= esc($defaultsJson) ?>"
    data-filter-ignored="<?= esc($ignoredJson) ?>"
>
    <div class="flex items-center justify-between gap-3">
        <h4 class="text-sm font-semibold text-gray-800"><?= esc($title) ?></h4>
        <?php if ($reactiveHasFilters || $hasFilters): ?>
            <a
                href="<?= esc($clearUrl) ?>"
                class="text-xs font-medium text-brand-700 hover:text-brand-800 hover:underline"
                <?php if ($reactiveHasFilters): ?>
                    x-cloak
                    x-show="hasActiveFilters()"
                <?php endif; ?>
            ><?= lang('App.clearFilters') ?></a>
        <?php endif; ?>
    </div>

    <?php if (is_string($fieldsView) && $fieldsView !== ''): ?>
        <?= view($fieldsView, is_array($fieldsData) ? $fieldsData : []) ?>
    <?php endif; ?>

    <div class="mt-3 flex items-center justify-end gap-2">
        <button type="submit" class="<?= esc(filter_submit_button_class((bool) $submitFullWidth)) ?>">
            <?= ui_icon('search', 'h-3.5 w-3.5') ?>
            <?= esc($submitLabel) ?>
        </button>
    </div>
</form>
