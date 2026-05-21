<?php if (empty($recentFiles)): ?>
    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
        <div class="mx-auto h-12 w-12 text-gray-400">
            <?= ui_icon('file-plus', 'h-12 w-12') ?>
        </div>
        <p class="mt-2 text-sm text-gray-600"><?= lang('Dashboard.no_recent_files') ?></p>
        <a href="<?= route_to('files') ?>" class="mt-4 inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
            <?= lang('Dashboard.manage_files') ?>
        </a>
    </div>
<?php else: ?>
    <div class="<?= esc(table_wrapper_class()) ?>">
        <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?> w-16"><?= lang('TableColumns.preview') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.file_name') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.category') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.size') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.date') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <?php foreach ($recentFiles as $file): ?>
                        <?php
                        $fileId   = $file['id'] ?? '';
                        $isImage  = (bool) ($file['is_image'] ?? false);
                        $thumbUrl = $file['variants']['sm']['url'] ?? ($isImage ? route_to('files.view', $fileId) : null);
                        ?>
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class()) ?>">
                                <?php if ($thumbUrl !== null): ?>
                                    <a href="<?= route_to('files.show', $fileId) ?>">
                                        <img src="<?= esc($thumbUrl) ?>"
                                             class="h-10 w-10 rounded-lg object-cover border border-gray-200 hover:scale-110 transition-transform shadow-sm"
                                             alt="<?= esc((string) ($file['original_name'] ?? '')) ?>">
                                    </a>
                                <?php else: ?>
                                    <div class="h-10 w-10 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200">
                                        <?= ui_icon('file', 'h-5 w-5 text-gray-400') ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="<?= esc(table_td_class('primary')) ?>">
                                <?= esc((string) ($file['original_name'] ?? $file['filename'] ?? '-')) ?>
                            </td>
                            <td class="<?= esc(table_td_class('subtle')) ?> text-xs uppercase">
                                <?= esc((string) ($file['category'] ?? '-')) ?>
                            </td>
                            <td class="<?= esc(table_td_class('muted')) ?>">
                                <?= esc((string) ($file['human_size'] ?? '-')) ?>
                            </td>
                            <td class="<?= esc(table_td_class('muted')) ?>">
                                <?= esc(format_date($file['uploaded_at'] ?? null)) ?>
                            </td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <div class="flex items-center gap-2">
                                    <a href="<?= route_to('files.show', $fileId) ?>" class="<?= esc(action_button_class()) ?>" title="<?= esc(lang('App.view')) ?>">
                                        <?= ui_icon('eye', 'h-3.5 w-3.5') ?>
                                        <span class="hidden md:inline"><?= lang('App.view') ?></span>
                                    </a>
                                    <a href="<?= route_to('files.download', $fileId) ?>" class="<?= esc(action_button_class()) ?>" title="<?= esc(lang('App.download')) ?>">
                                        <?= ui_icon('download', 'h-3.5 w-3.5') ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
