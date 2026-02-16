<form method="post" action="<?= site_url('reset-password') ?>" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= esc($token ?? old('token')) ?>">

    <div>
        <label class="block text-sm font-medium text-gray-700" for="password">Nuevo password</label>
        <input id="password" name="password" type="password" required
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="password_confirmation">Confirmar password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
    </div>

    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 hover:bg-brand-700">Actualizar password</button>
</form>
