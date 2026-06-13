<aside id="app-sidebar" class="bg-gray-900 text-gray-200 w-72 h-full fixed inset-y-0 left-0 z-40 flex flex-col overflow-x-hidden transform transition-transform duration-200 md:translate-x-0"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
    <div class="h-16 px-4 border-b border-gray-800 flex items-center justify-between">
        <span class="text-sm uppercase tracking-widest text-gray-400"><?= lang('App.menu') ?></span>
        <button class="md:hidden text-gray-400 hover:text-white" @click="sidebarOpen = false" aria-label="<?= esc(lang('App.close_navigation')) ?>">
            <span aria-hidden="true">x</span>
        </button>
    </div>

    <nav class="flex-1 min-h-0 overflow-y-auto p-3 space-y-1">
        <a href="<?= route_to('dashboard') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('dashboard') ?>">
            <?= ui_icon('dashboard') ?>
            <span><?= lang('App.dashboard') ?></span>
        </a>
        <a href="<?= route_to('profile') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('profile') ?>">
            <?= ui_icon('profile') ?>
            <span><?= lang('App.profile') ?></span>
        </a>
        <a href="<?= route_to('files') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('files') ?>">
            <?= ui_icon('files') ?>
            <span><?= lang('App.files') ?></span>
        </a>

        <?php
            $hasAdminItem = has_permission('users.read') || has_permission('audit.read') || has_permission('apikeys.read') || has_permission('metrics.read');
        ?>

        <?php if ($hasAdminItem): ?>
            <div class="pt-3 mt-3 border-t border-gray-800 text-xs uppercase text-gray-500"><?= lang('App.administration') ?></div>
            <?php if (has_permission('users.read')): ?>
                <a href="<?= route_to('admin.users') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/users*') ?>">
                    <?= ui_icon('users') ?>
                    <span><?= lang('App.users') ?></span>
                </a>
            <?php endif; ?>
            <?php if (has_permission('audit.read')): ?>
                <a href="<?= route_to('admin.audit') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/audit*') ?>">
                    <?= ui_icon('audit') ?>
                    <span><?= lang('App.audit') ?></span>
                </a>
            <?php endif; ?>
            <?php if (has_permission('apikeys.read')): ?>
                <a href="<?= route_to('admin.api_keys') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/api-keys*') ?>">
                    <?= ui_icon('api_keys') ?>
                    <span><?= lang('App.api_keys') ?></span>
                </a>
            <?php endif; ?>
            <?php if (has_permission('metrics.read')): ?>
                <a href="<?= route_to('admin.metrics') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/metrics') ?>">
                    <?= ui_icon('metrics') ?>
                    <span><?= lang('App.metrics') ?></span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (has_permission('iam.superadmin-access')): ?>
            <div class="pt-3 mt-3 border-t border-gray-800 text-xs uppercase text-gray-500"><?= lang('App.identity_access') ?></div>
            <a href="<?= route_to('admin.iam.roles') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/iam/roles*') ?>">
                <?= ui_icon('shield') ?>
                <span><?= lang('App.roles') ?></span>
            </a>
            <a href="<?= route_to('admin.iam.role_permissions') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/iam/role-permissions*') ?>">
                <?= ui_icon('lock') ?>
                <span><?= lang('Iam.role_permissions_title') ?></span>
            </a>
            <a href="<?= route_to('admin.iam.permissions') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/iam/permissions*') ?>">
                <?= ui_icon('lock') ?>
                <span><?= lang('App.permissions') ?></span>
            </a>
            <a href="<?= route_to('admin.iam.applications') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/iam/applications*') ?>">
                <?= ui_icon('layers') ?>
                <span><?= lang('Iam.applications_title') ?></span>
            </a>
        <?php endif; ?>
        <!-- [DYNAMIC_MODULES_ANCHOR] -->
    </nav>
</aside>

<div class="fixed inset-0 bg-black/30 z-30 md:hidden" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>
<div class="hidden md:block w-72 shrink-0"></div>
