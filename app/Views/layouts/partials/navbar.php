<header class="h-16 bg-white border-b border-gray-200 px-4 md:px-6 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <button class="md:hidden text-gray-600 hover:text-gray-900" @click="sidebarOpen = true">menu</button>
        <h2 class="text-sm text-gray-500"><?= esc($title ?? lang('App.panel')) ?></h2>
    </div>

    <div class="flex items-center gap-4">
        <div class="relative" x-data="{ open: false }">
            <button class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700" @click="open = !open" @click.away="open = false">
                <?= esc(strtoupper($currentLocale ?? 'es')) ?>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak class="absolute right-0 mt-2 w-32 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden z-50">
                <?php foreach (($supportedLocales ?? ['es', 'en']) as $loc): ?>
                    <a href="<?= site_url('language/set?locale=' . esc($loc, 'url')) ?>"
                       class="block px-4 py-2 text-sm <?= ($currentLocale ?? 'es') === $loc ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <?= esc(strtoupper($loc)) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="relative" x-data="{ open: false }">
            <button class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900" @click="open = !open" @click.away="open = false">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-semibold">
                    <?= esc(substr((string) (session('user.first_name') ?? 'U'), 0, 1)) ?>
                </span>
                <span><?= esc(trim((string) (session('user.first_name') ?? '') . ' ' . (string) (session('user.last_name') ?? ''))) ?></span>
            </button>
            <div x-show="open" x-cloak class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden z-50">
                <a href="<?= site_url('profile') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.myProfile') ?></a>
                <a href="<?= site_url('logout') ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50"><?= lang('App.logout') ?></a>
            </div>
        </div>
    </div>
</header>
