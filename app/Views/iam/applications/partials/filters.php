<?php /** @var array $limitOptions */ ?>

<div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
    <div class="xl:col-span-2">
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>"
            placeholder="<?= esc(lang('Iam.applications_search_placeholder')) ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <?= view('layouts/partials/filter_limit', ['limitOptions' => $limitOptions ?? [10, 25, 50, 100]]) ?>
</div>
