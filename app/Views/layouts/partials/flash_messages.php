<?php if (session()->has('success')): ?>
    <div role="status" aria-live="polite" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
        <?= esc(session('success')) ?>
    </div>
<?php endif; ?>

<?php if (session()->has('error')): ?>
    <div role="alert" aria-live="assertive" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
        <?= esc(session('error')) ?>
    </div>
<?php endif; ?>

<?php if (session()->has('fieldErrors')): ?>
    <?php $fieldErrors = session('fieldErrors'); ?>
    <?php if (is_array($fieldErrors) && count($fieldErrors) > 0): ?>
        <div role="alert" aria-live="assertive" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            <?= esc(lang('App.errorsFound')) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (session()->has('warning')): ?>
    <div role="status" aria-live="polite" class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm font-medium text-yellow-800">
        <?= esc(session('warning')) ?>
    </div>
<?php endif; ?>
