<div class="mb-4">
    <a href="<?= site_url('admin/audit') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Audit.backToList') ?></a>
</div>

<?php if (! empty($error)): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-red-600"><?= esc($error) ?></p>
    </div>
<?php elseif (! empty($log)): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('Audit.details') ?></h3>

            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-gray-500">ID</dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($log['id'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.user') ?></dt>
                    <dd class="mt-1 text-gray-900">
                        <?= esc((string) ($log['user_email'] ?? '-')) ?>
                        <?php if (! empty($log['user_id'])): ?>
                            <a href="<?= site_url('admin/users/' . esc((string) $log['user_id'], 'url')) ?>" class="ml-2 text-brand-600 hover:text-brand-700 text-xs"><?= lang('Audit.viewUser') ?></a>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.action') ?></dt>
                    <dd class="mt-1">
                        <span class="inline-flex rounded-full px-2 py-1 text-xs <?= audit_action_badge($log['action'] ?? '') ?>">
                            <?= esc((string) ($log['action'] ?? '-')) ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.entity') ?></dt>
                    <dd class="mt-1 text-gray-900">
                        <?= esc((string) ($log['entity_type'] ?? '-')) ?>
                        <?php if (! empty($log['entity_id'])): ?>
                            <span class="text-gray-400">#<?= esc((string) $log['entity_id']) ?></span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.ipAddress') ?></dt>
                    <dd class="mt-1 text-gray-900 font-mono text-xs"><?= esc((string) ($log['ip_address'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.userAgent') ?></dt>
                    <dd class="mt-1 text-gray-900 text-xs break-all"><?= esc((string) ($log['user_agent'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.date') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc(format_date($log['created_at'] ?? null)) ?></dd>
                </div>
            </dl>
        </section>

        <div class="space-y-6">
            <?php if (! empty($log['old_values'])): ?>
                <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Audit.oldValues') ?></h3>
                    <pre class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs text-gray-700 overflow-x-auto"><?php
                        $old = $log['old_values'];
                echo esc(is_string($old) ? $old : json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                ?></pre>
                </section>
            <?php endif; ?>

            <?php if (! empty($log['new_values'])): ?>
                <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Audit.newValues') ?></h3>
                    <pre class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs text-gray-700 overflow-x-auto"><?php
                    $new = $log['new_values'];
                echo esc(is_string($new) ? $new : json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                ?></pre>
                </section>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
