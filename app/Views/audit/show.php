<div class="mb-4">
    <a href="<?= route_to('admin.audit') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Audit.back_to_list') ?></a>
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
                    <dt class="text-gray-500"><?= lang('App.id') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($log['id'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.user') ?></dt>
                    <dd class="mt-1 text-gray-900">
                        <?= esc((string) ($log['user_email'] ?? '-')) ?>
                        <?php if (! empty($log['user_id'])): ?>
                            <a href="<?= route_to('admin.users.show', (string) $log['user_id']) ?>" class="ml-2 text-brand-600 hover:text-brand-700 text-xs"><?= lang('Audit.view_user') ?></a>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.action') ?></dt>
                    <dd class="mt-1 flex gap-2 items-center">
                        <span class="inline-flex rounded-full px-2 py-1 text-xs <?= audit_action_badge($log['action'] ?? '') ?>">
                            <?= esc(localized_audit_action((string) ($log['action'] ?? '-'))) ?>
                        </span>
                        <?php if (! empty($log['result'])): ?>
                            <span class="inline-flex rounded-full px-2 py-1 text-xs <?= audit_result_badge($log['result']) ?>">
                                <?= esc(localized_audit_result($log['result'])) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (! empty($log['severity'])): ?>
                            <span class="inline-flex rounded-full px-2 py-1 text-[10px] <?= audit_severity_badge($log['severity']) ?>">
                                <?= esc(localized_audit_severity($log['severity'])) ?>
                            </span>
                        <?php endif; ?>
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
                    <dt class="text-gray-500"><?= lang('Audit.ip_address') ?></dt>
                    <dd class="mt-1 text-gray-900 font-mono text-xs"><?= esc((string) ($log['ip_address'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.user_agent') ?></dt>
                    <dd class="mt-1 text-gray-900 text-xs break-all"><?= esc((string) ($log['user_agent'] ?? '-')) ?></dd>
                </div>
                <?php if (! empty($log['request_id'])): ?>
                    <div>
                        <dt class="text-gray-500"><?= lang('Audit.request_id') ?></dt>
                        <dd class="mt-1 text-gray-900 font-mono text-xs"><?= esc((string) $log['request_id']) ?></dd>
                    </div>
                <?php endif; ?>
                <div>
                    <dt class="text-gray-500"><?= lang('Audit.date') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc(format_date($log['created_at'] ?? null)) ?></dd>
                </div>
            </dl>
        </section>

        <div class="space-y-6">
            <?php if (! empty($log['metadata'])): ?>
                <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Audit.metadata') ?></h3>
                    <pre class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs text-gray-700 overflow-x-auto"><?php
                        $meta = $log['metadata'];
                echo esc(is_string($meta) ? $meta : json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                ?></pre>
                </section>
            <?php endif; ?>

            <?php if (! empty($log['old_values'])): ?>
                <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Audit.old_values') ?></h3>
                    <pre class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs text-gray-700 overflow-x-auto"><?php
                        $old = $log['old_values'];
                echo esc(is_string($old) ? $old : json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                ?></pre>
                </section>
            <?php endif; ?>

            <?php if (! empty($log['new_values'])): ?>
                <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Audit.new_values') ?></h3>
                    <pre class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs text-gray-700 overflow-x-auto"><?php
                    $new = $log['new_values'];
                echo esc(is_string($new) ? $new : json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                ?></pre>
                </section>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
