<div class="space-y-4">
    <div class="rounded-lg border px-4 py-3 text-sm <?= !empty($verified) ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' ?>">
        <?= esc($message ?? '') ?>
    </div>

    <a href="/login" class="block w-full text-center rounded-lg bg-brand-600 text-white px-4 py-2 hover:bg-brand-700">
        Ir al login
    </a>
</div>
