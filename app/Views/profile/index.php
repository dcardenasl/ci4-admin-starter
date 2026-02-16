<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900">Informacion personal</h3>
        <form method="post" action="<?= site_url('profile') ?>" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="first_name">Nombre</label>
                    <input id="first_name" name="first_name" type="text" value="<?= esc(old('first_name', $user['first_name'] ?? '')) ?>" required
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="last_name">Apellido</label>
                    <input id="last_name" name="last_name" type="text" value="<?= esc(old('last_name', $user['last_name'] ?? '')) ?>" required
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="avatar_url">Avatar URL</label>
                <input id="avatar_url" name="avatar_url" type="url" value="<?= esc(old('avatar_url', $user['avatar_url'] ?? '')) ?>"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
            </div>
            <button type="submit" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700">Guardar cambios</button>
        </form>
    </section>

    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900">Seguridad</h3>
        <form method="post" action="<?= site_url('profile/change-password') ?>" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="current_password">Password actual</label>
                <input id="current_password" name="current_password" type="password" required
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
            </div>
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
            <button type="submit" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700">Actualizar password</button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-200">
            <h4 class="font-medium text-gray-900">Verificacion de correo</h4>
            <p class="mt-1 text-sm text-gray-600">
                Estado:
                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= !empty($user['email_verified_at']) ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                    <?= !empty($user['email_verified_at']) ? 'Verificado' : 'Pendiente' ?>
                </span>
            </p>
            <?php if (empty($user['email_verified_at'])): ?>
                <form method="post" action="<?= site_url('profile/resend-verification') ?>" class="mt-3">
                    <?= csrf_field() ?>
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Reenviar verificacion
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>
