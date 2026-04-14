<li class="relative pb-8">
    <?php if (! ($isLast ?? false)): ?>
        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
    <?php endif; ?>
    <div class="relative flex space-x-3">
        <div>
            <?php
            $action = strtolower((string) ($item['action'] ?? ''));
            $actionIcon = match ($action) {
                'create', 'upload' => 'plus',
                'update', 'edit'   => 'edit',
                'delete', 'remove' => 'trash',
                default            => 'activity',
            };
            ?>
            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white <?= audit_action_badge($action) ?>">
                <?= ui_icon($actionIcon, 'h-4 w-4 text-white') ?>
            </span>
        </div>
        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
            <div>
                <p class="text-sm text-gray-600">
                    <span class="font-medium text-gray-900"><?= esc($item['user_email'] ?? $item['user_id'] ?? 'System') ?></span>
                    <?= esc($item['action'] ?? 'activity') ?>
                    <span class="font-medium text-gray-900"><?= esc($item['entity_type'] ?? '') ?></span>
                </p>
            </div>
            <div class="whitespace-nowrap text-right text-xs text-gray-500">
                <time datetime="<?= esc($item['created_at'] ?? '') ?>"><?= format_date($item['created_at'] ?? null) ?></time>
            </div>
        </div>
    </div>
</li>
