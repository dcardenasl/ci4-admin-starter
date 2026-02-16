<form method="post" action="/forgot-password" class="space-y-4">
    <?= csrf_field() ?>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="email">Correo</label>
        <input id="email" name="email" type="email" value="<?= old('email') ?>" required
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
    </div>
    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 hover:bg-brand-700">Enviar enlace</button>
</form>

<div class="mt-4 text-sm text-center">
    <a href="/login" class="text-brand-600 hover:text-brand-700">Volver al login</a>
</div>
