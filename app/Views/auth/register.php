<form method="post" action="<?= site_url('register') ?>" class="space-y-4" x-data="{ password: '' }">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700" for="first_name">Nombre</label>
            <input id="first_name" name="first_name" type="text" value="<?= old('first_name') ?>" required
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700" for="last_name">Apellido</label>
            <input id="last_name" name="last_name" type="text" value="<?= old('last_name') ?>" required
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="email">Correo</label>
        <input id="email" name="email" type="email" value="<?= old('email') ?>" required
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="password">Password</label>
        <input id="password" name="password" type="password" x-model="password" required
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
        <p class="mt-1 text-xs"
           :class="password.length >= 12 ? 'text-green-700' : password.length >= 8 ? 'text-yellow-700' : 'text-red-700'"
           x-text="password.length >= 12 ? 'Password fuerte' : password.length >= 8 ? 'Password media' : 'Password debil'"></p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="password_confirmation">Confirmar password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
    </div>

    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 hover:bg-brand-700">Crear cuenta</button>
</form>

<div class="mt-4 text-sm text-gray-600 text-center">
    <a href="<?= site_url('login') ?>" class="text-brand-600 hover:text-brand-700">Ya tengo cuenta</a>
</div>
