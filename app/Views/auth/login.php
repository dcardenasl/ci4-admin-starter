<form method="post" action="/login" class="space-y-4">
    <?= csrf_field() ?>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="email">Correo</label>
        <input id="email" name="email" type="email" value="<?= old('email') ?>" required
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="password">Password</label>
        <input id="password" name="password" type="password" required
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
    </div>
    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 hover:bg-brand-700">Entrar</button>
</form>

<div class="mt-4 text-sm text-gray-600 flex items-center justify-between">
    <a href="/forgot-password" class="text-brand-600 hover:text-brand-700">Olvide mi password</a>
    <a href="/register" class="text-brand-600 hover:text-brand-700">Crear cuenta</a>
</div>
