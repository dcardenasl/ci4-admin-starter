<?php
$roles = $roles ?? [];
$applications = $applications ?? [];
$assignments = $assignments ?? [];
$activeTab = $activeTab !== '' ? $activeTab : (string) ($roles[0]['id'] ?? '');
?>
<div class="mb-4 flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Iam.role_permissions_title')) ?></h3>
    <a href="<?= route_to('admin.iam.roles') ?>" class="<?= esc(action_button_class()) ?>"><?= esc(lang('Iam.roles_title')) ?></a>
</div>

<?php if (! empty($error)): ?>
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= esc((string) $error) ?></div>
<?php elseif ($roles === []): ?>
    <p class="text-sm text-gray-500"><?= esc(lang('Iam.roles_empty')) ?></p>
<?php else: ?>
    <div x-data="{
        tab: '<?= esc($activeTab) ?>',
        setWithin(scope, checked) {
            if (! scope) {
                return;
            }
            scope.querySelectorAll('input[type=checkbox][name=\'permission_ids[]\']').forEach((input) => {
                if (! input.disabled) {
                    input.checked = checked;
                }
            });
            // Programmatic checkbox toggles don't fire a native 'change' event;
            // dispatch one so the enclosing form's dirty-tracking picks it up.
            scope.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }" class="space-y-4">
        <div class="flex flex-wrap gap-2 border-b border-gray-200">
            <?php foreach ($roles as $role): ?>
                <?php $roleId = (string) ($role['id'] ?? ''); ?>
                <button type="button"
                    class="px-3 py-2 text-sm font-medium border-b-2"
                    :class="tab === '<?= esc($roleId) ?>' ? 'border-brand-600 text-brand-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    @click="tab = '<?= esc($roleId) ?>'">
                    <?= esc((string) ($role['name'] ?? $role['code'] ?? $roleId)) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($roles as $role): ?>
            <?php $roleId = (string) ($role['id'] ?? ''); ?>
            <?php $assigned = array_map('strval', $assignments[(int) $roleId] ?? $assignments[$roleId] ?? []); ?>
            <form method="post" action="<?= route_to('admin.iam.role_permissions.save', $roleId) ?>" x-show="tab === '<?= esc($roleId) ?>'" data-role-id="<?= esc($roleId) ?>"
                x-data="{
                    isDirty: false,
                    selectedCount: <?= count($assigned) ?>,
                    updateCount() {
                        this.selectedCount = Array.from(this.$el.querySelectorAll('input[name=\'permission_ids[]\']:checked')).length;
                    }
                }"
                @change="isDirty = true; updateCount()"
                class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="permission_ids[]" value="">

                <div class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                    <span class="text-sm font-medium text-gray-700"><?= esc(lang('Iam.role_permissions_bulk_role')) ?></span>
                    <button type="button" class="<?= esc(action_button_class()) ?>" @click="setWithin($el.closest('form'), true)">
                        <?= esc(lang('Iam.permissions_select_all')) ?>
                    </button>
                    <button type="button" class="<?= esc(action_button_class()) ?>" @click="setWithin($el.closest('form'), false)">
                        <?= esc(lang('Iam.permissions_clear_all')) ?>
                    </button>
                    <span class="ml-auto text-xs text-gray-500"><?= esc(lang('Iam.role_permissions_hint')) ?></span>
                    <span class="shrink-0 rounded-full border border-gray-200 bg-white px-2 py-1 text-xs font-semibold tabular-nums text-gray-700">
                        <span x-text="selectedCount"></span> <?= esc(lang('Iam.role_permissions_selected_label')) ?>
                    </span>
                </div>

                <?php foreach ($applications as $application): ?>
                    <?php
                    $appPermissions = $application['permissions'] ?? [];
                    $byResource = [];
                    foreach ($appPermissions as $permission) {
                        $byResource[(string) ($permission['resource'] ?? '-')][] = $permission;
                    }
                    ?>
                    <section data-app-id="<?= esc((string) ($application['id'] ?? '')) ?>" class="border border-gray-200 rounded-lg p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h4 class="font-semibold text-gray-900"><?= esc((string) ($application['name'] ?? $application['code'] ?? '')) ?></h4>
                                <p class="text-xs text-gray-500">
                                    <?= esc((string) ($application['code'] ?? '')) ?>
                                    <?php if (is_countable($appPermissions)): ?>
                                        <span class="ml-1">· <?= count($appPermissions) ?> <?= esc(strtolower(lang('App.permissions'))) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="<?= esc(action_button_class()) ?>" @click="setWithin($el.closest('[data-app-id]'), true)">
                                    <?= esc(lang('Iam.permissions_select_all')) ?>
                                </button>
                                <button type="button" class="<?= esc(action_button_class()) ?>" @click="setWithin($el.closest('[data-app-id]'), false)">
                                    <?= esc(lang('Iam.permissions_clear_all')) ?>
                                </button>
                            </div>
                        </div>

                        <?php if ($byResource === []): ?>
                            <p class="mt-3 text-sm text-gray-500"><?= esc(lang('Iam.permissions_empty')) ?></p>
                        <?php else: ?>
                            <div class="mt-4 space-y-4">
                                <?php foreach ($byResource as $resource => $permissions): ?>
                                    <div data-resource="<?= esc($resource) ?>" class="rounded-lg border border-gray-100 bg-white p-3">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500"><?= esc($resource) ?></div>
                                                <div class="text-xs text-gray-400"><?= count($permissions) ?> <?= esc(strtolower(lang('App.permissions'))) ?></div>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" class="<?= esc(action_button_class()) ?>" @click="setWithin($el.closest('[data-resource]'), true)">
                                                    <?= esc(lang('Iam.permissions_select_group')) ?>
                                                </button>
                                                <button type="button" class="<?= esc(action_button_class()) ?>" @click="setWithin($el.closest('[data-resource]'), false)">
                                                    <?= esc(lang('Iam.permissions_clear_group')) ?>
                                                </button>
                                            </div>
                                        </div>

                                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                        <?php foreach ($permissions as $permission): ?>
                                            <?php $permissionId = (string) ($permission['id'] ?? ''); ?>
                                            <label class="inline-flex items-start gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">
                                                <input type="checkbox" name="permission_ids[]" value="<?= esc($permissionId) ?>"
                                                    <?= in_array($permissionId, $assigned, true) ? 'checked' : '' ?>
                                                    class="mt-1 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                <span>
                                                    <code class="font-medium text-gray-900"><?= esc((string) ($permission['code'] ?? '')) ?></code>
                                                    <?php if (! empty($permission['action'])): ?>
                                                        <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-[11px] font-medium text-gray-600"><?= esc((string) $permission['action']) ?></span>
                                                    <?php endif; ?>
                                                    <span class="block text-xs text-gray-500"><?= esc((string) ($permission['description'] ?? '')) ?></span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>

                <div class="flex items-center gap-3">
                    <button type="submit" class="<?= esc(action_button_class('primary')) ?>"><?= esc(lang('App.save')) ?></button>
                    <span x-show="isDirty" x-cloak
                        class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                        <?= ui_icon('triangle-alert', 'h-3.5 w-3.5') ?>
                        <?= esc(lang('App.unsaved_changes')) ?>
                    </span>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
