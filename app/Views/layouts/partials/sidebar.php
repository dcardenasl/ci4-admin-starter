<aside class="bg-gray-900 text-gray-200 w-72 fixed inset-y-0 left-0 z-40 transform transition-transform duration-200 md:translate-x-0"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
    <div class="h-16 px-4 border-b border-gray-800 flex items-center justify-between">
        <span class="text-sm uppercase tracking-widest text-gray-400"><?= lang('App.menu') ?></span>
        <button class="md:hidden text-gray-400 hover:text-white" @click="sidebarOpen = false">x</button>
    </div>

    <nav class="p-3 space-y-1">
        <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('dashboard') ?>">
            <?= ui_icon('dashboard') ?>
            <span><?= lang('App.dashboard') ?></span>
        </a>
        <a href="<?= site_url('profile') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('profile') ?>">
            <?= ui_icon('profile') ?>
            <span><?= lang('App.profile') ?></span>
        </a>
        <a href="<?= site_url('files') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('files') ?>">
            <?= ui_icon('files') ?>
            <span><?= lang('App.files') ?></span>
        </a>

        <?php if (has_admin_access((string) (session('user.role') ?? ''))): ?>
            <div class="pt-3 mt-3 border-t border-gray-800 text-xs uppercase text-gray-500"><?= lang('App.administration') ?></div>
            <a href="<?= site_url('admin/users') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/users*') ?>">
                <?= ui_icon('users') ?>
                <span><?= lang('App.users') ?></span>
            </a>
            <a href="<?= site_url('admin/audit') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/audit*') ?>">
                <?= ui_icon('audit') ?>
                <span><?= lang('App.audit') ?></span>
            </a>
            <a href="<?= site_url('admin/api-keys') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/api-keys*') ?>">
                <?= ui_icon('apiKeys') ?>
                <span><?= lang('App.apiKeys') ?></span>
            </a>
            <a href="<?= site_url('admin/metrics') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/metrics') ?>">
                <?= ui_icon('metrics') ?>
                <span><?= lang('App.metrics') ?></span>
            </a>
        <?php endif; ?>
    </nav>
</aside>

<div class="fixed inset-0 bg-black/30 z-30 md:hidden" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>
<div class="hidden md:block w-72 shrink-0"></div>
