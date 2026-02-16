<aside class="bg-gray-900 text-gray-200 w-72 fixed inset-y-0 left-0 z-40 transform transition-transform duration-200 md:translate-x-0"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
    <div class="h-16 px-4 border-b border-gray-800 flex items-center justify-between">
        <span class="text-sm uppercase tracking-widest text-gray-400">Menu</span>
        <button class="md:hidden text-gray-400 hover:text-white" @click="sidebarOpen = false">x</button>
    </div>

    <nav class="p-3 space-y-1">
        <a href="/dashboard" class="block rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('dashboard') ?>">
            Dashboard
        </a>
        <a href="/profile" class="block rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('profile') ?>">
            Perfil
        </a>
        <a href="/files" class="block rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('files') ?>">
            Archivos
        </a>

        <?php if ((session('user.role') ?? null) === 'admin'): ?>
            <div class="pt-3 mt-3 border-t border-gray-800 text-xs uppercase text-gray-500">Administracion</div>
            <a href="/admin/users" class="block rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/users*') ?>">
                Usuarios
            </a>
            <a href="/admin/audit" class="block rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/audit*') ?>">
                Auditoria
            </a>
            <a href="/admin/metrics" class="block rounded-lg px-3 py-2 text-sm hover:bg-brand-50 hover:text-brand-700 <?= active_nav('admin/metrics') ?>">
                Metricas
            </a>
        <?php endif; ?>
    </nav>
</aside>

<div class="fixed inset-0 bg-black/30 z-30 md:hidden" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>
<div class="hidden md:block w-72 shrink-0"></div>
