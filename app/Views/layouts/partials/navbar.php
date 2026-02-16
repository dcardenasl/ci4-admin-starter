<header class="h-16 bg-white border-b border-gray-200 px-4 md:px-6 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <button class="md:hidden text-gray-600 hover:text-gray-900" @click="sidebarOpen = true">menu</button>
        <h2 class="text-sm text-gray-500"><?= esc($title ?? 'Panel') ?></h2>
    </div>

    <div class="relative" x-data="{ open: false }">
        <button class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900" @click="open = !open" @click.away="open = false">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-semibold">
                <?= esc(substr((string) (session('user.first_name') ?? 'U'), 0, 1)) ?>
            </span>
            <span><?= esc(trim((string) (session('user.first_name') ?? '') . ' ' . (string) (session('user.last_name') ?? ''))) ?></span>
        </button>
        <div x-show="open" x-cloak class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Mi perfil</a>
            <a href="/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Cerrar sesion</a>
        </div>
    </div>
</header>
