<li class="relative pb-8">
    <?php if (! ($isLast ?? false)): ?>
        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
    <?php endif; ?>
    <div class="relative flex space-x-3">
        <div>
            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white <?= audit_action_badge($item['action'] ?? '', true) ?>">
                <?= ui_icon(match ($item['action'] ?? '') {
                    'create', 'upload' => 'plus',
                    'update', 'edit'   => 'edit-3',
                    'delete', 'remove' => 'trash-2',
                    'login'            => 'log-in',
                    'logout'           => 'log-out',
                    default            => 'activity'
                }, 'h-4 w-4 text-white') ?>
            </span>
        </div>
        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
            <div>
                <p class="text-sm text-gray-600">
                    <span class="font-medium text-gray-900"><?= esc($item['userEmail'] ?? $item['user_email'] ?? $item['userId'] ?? $item['user_id'] ?? 'System') ?></span>
                    <?= esc($item['action'] ?? 'activity') ?>
                    <span class="font-medium text-gray-900"><?= esc($item['entityType'] ?? $item['entity_type'] ?? '') ?></span>
                </p>
            </div>
            <div class="whitespace-nowrap text-right text-xs text-gray-500">
                <time datetime="<?= esc($item['createdAt'] ?? $item['created_at'] ?? '') ?>"><?= format_date($item['createdAt'] ?? $item['created_at'] ?? null) ?></time>
            </div>
        </div>
    </div>
</li>