<?php
/** @var array<string, mixed> $file */
/** @var list<array{resource:string, resource_id:int, role:string, label:string}> $usages */
$file     = $file ?? [];
$usages   = $usages ?? [];
$id       = (string) ($file['id'] ?? '');
$variants = is_array($file['variants'] ?? null) ? $file['variants'] : [];
$smUrl    = is_array($variants['sm'] ?? null) ? (string) ($variants['sm']['url'] ?? '') : '';
?>
<div class="mb-4 flex items-center justify-between">
    <a href="<?= route_to('files') ?>" class="text-sm text-brand-600 hover:text-brand-700">
        &larr; <?= esc(lang('App.back')) ?>
    </a>
    <div class="flex items-center gap-2">
        <a href="<?= route_to('files') ?>/<?= esc($id) ?>/download" class="<?= esc(action_button_class()) ?>">
            <?= ui_icon('download', 'h-3.5 w-3.5') ?> <?= esc(lang('App.download')) ?>
        </a>
        <form method="post" action="<?= route_to('files') ?>/<?= esc($id) ?>/delete"
              x-data @submit.prevent="$store.confirm.show('<?= esc(lang('Files.confirm_delete')) ?>', () => $el.submit())">
            <?= csrf_field() ?>
            <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
                <?= ui_icon('trash', 'h-3.5 w-3.5') ?> <?= esc(lang('App.delete')) ?>
            </button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Preview + technical info -->
    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 lg:col-span-1">
        <div class="aspect-square w-full overflow-hidden rounded-lg border border-gray-100 bg-gray-50 flex items-center justify-center">
            <?php if (! empty($file['is_image'])): ?>
                <img src="<?= esc($smUrl !== '' ? $smUrl : (route_to('files') . '/' . $id . '/view')) ?>"
                     alt="<?= esc((string) ($file['alt_text'] ?? $file['original_name'] ?? '')) ?>"
                     class="w-full h-full object-contain">
            <?php else: ?>
                <div class="text-gray-300 flex flex-col items-center">
                    <?= ui_icon('file', 'h-20 w-20') ?>
                    <p class="mt-2 text-xs text-gray-500 uppercase"><?= esc((string) ($file['mime_type'] ?? '')) ?></p>
                </div>
            <?php endif; ?>
        </div>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500"><?= esc(lang('Files.file_name')) ?></dt>
                <dd class="font-medium text-gray-900 truncate" title="<?= esc((string) ($file['original_name'] ?? '')) ?>">
                    <?= esc((string) ($file['original_name'] ?? '-')) ?>
                </dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500"><?= esc(lang('Files.category')) ?></dt>
                <dd class="font-medium text-gray-900 uppercase text-xs"><?= esc((string) ($file['category'] ?? '-')) ?></dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500"><?= esc(lang('Files.type')) ?></dt>
                <dd class="font-medium text-gray-900 text-xs"><?= esc((string) ($file['mime_type'] ?? '-')) ?></dd>
            </div>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500"><?= esc(lang('Files.size')) ?></dt>
                <dd class="font-medium text-gray-900"><?= esc((string) ($file['human_size'] ?? '-')) ?></dd>
            </div>
            <?php if (! empty($file['width']) && ! empty($file['height'])): ?>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500"><?= esc(lang('Files.dimensions')) ?></dt>
                    <dd class="font-medium text-gray-900"><?= esc((string) $file['width']) ?> × <?= esc((string) $file['height']) ?> px</dd>
                </div>
            <?php endif; ?>
            <?php if (! empty($file['duration_seconds'])): ?>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500"><?= esc(lang('Files.duration')) ?></dt>
                    <dd class="font-medium text-gray-900">
                        <?php
                        $secs = (int) $file['duration_seconds'];
                        $h    = intdiv($secs, 3600);
                        $m    = intdiv($secs % 3600, 60);
                        $s    = $secs % 60;
                        echo $h > 0
                            ? sprintf('%d:%02d:%02d', $h, $m, $s)
                            : sprintf('%d:%02d', $m, $s);
                        ?>
                    </dd>
                </div>
            <?php endif; ?>
            <?php if (! empty($file['page_count'])): ?>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500"><?= esc(lang('Files.page_count')) ?></dt>
                    <dd class="font-medium text-gray-900"><?= esc((string) $file['page_count']) ?></dd>
                </div>
            <?php endif; ?>
            <div class="flex justify-between gap-2">
                <dt class="text-gray-500"><?= esc(lang('Files.date')) ?></dt>
                <dd class="font-medium text-gray-900"><?= esc((string) ($file['uploaded_at'] ?? '-')) ?></dd>
            </div>
        </dl>

        <?php if (is_array($file['variants'] ?? null) && $file['variants'] !== []): ?>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs font-semibold uppercase text-gray-500 mb-2"><?= esc(lang('Files.variants')) ?></p>
                <ul class="space-y-1 text-xs">
                    <?php foreach ($file['variants'] as $variantKey => $variant): ?>
                        <?php if (! is_array($variant)) {
                            continue;
                        } ?>
                        <li class="flex justify-between gap-2 items-center">
                            <span class="text-gray-500 uppercase"><?= esc((string) $variantKey) ?></span>
                            <a href="<?= esc((string) ($variant['url'] ?? '')) ?>" target="_blank" rel="noopener" class="text-brand-600 hover:underline truncate">
                                <?= esc((string) ($variant['width'] ?? '?')) ?>×<?= esc((string) ($variant['height'] ?? '?')) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </section>

    <!-- Editorial metadata + Where-used -->
    <div class="lg:col-span-2 space-y-6">
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Files.replace')) ?></h3>
            <p class="text-sm text-gray-500 mt-1"><?= esc(lang('Files.replace_help')) ?></p>
            <form method="post" action="<?= route_to('files.replace', $id) ?>" enctype="multipart/form-data" class="mt-3 flex flex-col sm:flex-row sm:items-center gap-3">
                <?= csrf_field() ?>
                <input type="file" name="file" required class="block text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-brand-700 hover:file:bg-brand-100">
                <button type="submit" class="<?= esc(action_button_class('primary')) ?>">
                    <?= ui_icon('upload', 'h-3.5 w-3.5') ?> <?= esc(lang('Files.replace_button')) ?>
                </button>
            </form>
            <form method="post" action="<?= route_to('files.regenerate', $id) ?>" class="mt-3">
                <?= csrf_field() ?>
                <button type="submit" class="<?= esc(action_button_class()) ?>" title="<?= esc(lang('Files.regenerate_help')) ?>">
                    <?= ui_icon('refresh-ccw', 'h-3.5 w-3.5') ?> <?= esc(lang('Files.regenerate')) ?>
                </button>
            </form>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Files.metadata_title')) ?></h3>
            <form method="post" action="<?= route_to('files.metadata', $id) ?>" class="mt-4 space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="alt_text"><?= esc(lang('Files.alt_text')) ?></label>
                    <input id="alt_text" name="alt_text" type="text" maxlength="255"
                           value="<?= esc((string) old('alt_text', (string) ($file['alt_text'] ?? ''))) ?>"
                           class="<?= esc(input_class('alt_text')) ?>">
                    <p class="text-xs text-gray-500 mt-1"><?= esc(lang('Files.alt_text_help')) ?></p>
                    <?= render_field_error('alt_text') ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="caption"><?= esc(lang('Files.caption')) ?></label>
                    <textarea id="caption" name="caption" rows="3" class="<?= esc(input_class('caption')) ?>"><?= esc((string) old('caption', (string) ($file['caption'] ?? ''))) ?></textarea>
                    <?= render_field_error('caption') ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="credit"><?= esc(lang('Files.credit')) ?></label>
                    <input id="credit" name="credit" type="text" maxlength="255"
                           value="<?= esc((string) old('credit', (string) ($file['credit'] ?? ''))) ?>"
                           class="<?= esc(input_class('credit')) ?>">
                    <?= render_field_error('credit') ?>
                </div>
                <div>
                    <button type="submit" class="<?= esc(action_button_class('primary')) ?>">
                        <?= esc(lang('Files.metadata_save')) ?>
                    </button>
                </div>
            </form>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Files.where_used')) ?></h3>
            <?php if ($usages === []): ?>
                <p class="mt-3 text-sm text-gray-500"><?= esc(lang('Files.where_used_empty')) ?></p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-gray-100">
                    <?php foreach ($usages as $usage): ?>
                        <li class="py-2 flex items-center justify-between gap-3 text-sm">
                            <div>
                                <p class="font-medium text-gray-900"><?= esc((string) ($usage['label'] ?? '')) ?></p>
                                <p class="text-xs text-gray-500"><?= esc((string) ($usage['resource'] ?? '')) ?> #<?= esc((string) ($usage['resource_id'] ?? '')) ?></p>
                            </div>
                            <span class="text-xs text-gray-400 uppercase"><?= esc((string) ($usage['role'] ?? '')) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
