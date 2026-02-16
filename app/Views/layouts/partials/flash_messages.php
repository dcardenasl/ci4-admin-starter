<?php if (session()->has('success')): ?>
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        <?= esc(session('success')) ?>
    </div>
<?php endif; ?>

<?php if (session()->has('error')): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?= esc(session('error')) ?>
    </div>
<?php endif; ?>

<?php if (session()->has('warning')): ?>
    <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
        <?= esc(session('warning')) ?>
    </div>
<?php endif; ?>
