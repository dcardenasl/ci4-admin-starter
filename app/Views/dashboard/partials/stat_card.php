<article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center gap-4">
    <div class="flex-shrink-0 p-3 bg-brand-50 rounded-lg text-brand-600">
        <?= ui_icon($icon ?? 'circle', 'h-6 w-6') ?>
    </div>
    <div>
        <p class="text-sm font-medium text-gray-500"><?= esc($label) ?></p>
        <div class="flex items-baseline gap-1">
            <p class="text-2xl font-semibold text-gray-900"><?= esc((string) $value) ?></p>
            <?php if (isset($suffix)): ?>
                <span class="text-xs text-gray-500 font-medium"><?= esc($suffix) ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>