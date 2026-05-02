<?php
/**
 * Reusable file picker field partial.
 *
 * Variables:
 *   $name        — hidden input name (required)
 *   $value       — current file ID (optional, default: '')
 *   $label       — placeholder label (optional, default: lang('Files.picker_title'))
 *   $accept      — MIME filter hint for upload tab (optional, e.g. 'image/*')
 *   $filterType  — pre-select category in picker ('image', 'document', 'video', 'audio') (optional)
 *
 * Usage:
 *   <?= view('layouts/partials/file_picker_field', [
 *       'name'       => 'cover_image_id',
 *       'value'      => $data['cover_image_id'] ?? '',
 *       'label'      => lang('Files.picker_select_file'),
 *       'accept'     => 'image/*',
 *       'filterType' => 'image',
 *   ]) ?>
 */

$fpName   = $name       ?? 'file_id';
$fpValue  = (string) ($value  ?? '');
$fpLabel  = $label      ?? lang('Files.picker_select_file');
$fpAccept = $accept     ?? '';
$fpFilter = $filterType ?? '';
?>
<div x-data="filePickerField({
        name: '<?= esc($fpName, 'js') ?>',
        value: '<?= esc($fpValue, 'js') ?>',
        accept: '<?= esc($fpAccept, 'js') ?>',
        filterType: '<?= esc($fpFilter, 'js') ?>'
    })"
     x-init="init()">

    <input type="hidden" :name="fieldName" :value="fileId">

    <!-- File selected: show preview + change/remove buttons -->
    <div x-show="fileId !== ''" x-cloak
         class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">

        <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-100 bg-white">
            <template x-if="fileInfo.is_image && fileInfo.url !== ''">
                <img :src="fileInfo.url" :alt="fileInfo.original_name"
                     class="h-full w-full object-cover">
            </template>
            <template x-if="!fileInfo.is_image || fileInfo.url === ''">
                <?= ui_icon('file', 'h-8 w-8 text-gray-400') ?>
            </template>
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-gray-900"
               x-text="fileInfo.original_name || '—'"></p>
            <p class="truncate text-xs text-gray-500" x-text="fileInfo.human_size"></p>
            <p class="text-xs capitalize text-gray-400" x-text="fileInfo.category"></p>
        </div>

        <div class="flex flex-shrink-0 flex-col gap-1">
            <button type="button" @click="openPicker()"
                    class="rounded px-2 py-1 text-xs bg-brand-50 text-brand-700 hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <?= esc(lang('Files.picker_change')) ?>
            </button>
            <button type="button" @click="clearFile()"
                    class="rounded px-2 py-1 text-xs bg-red-50 text-red-600 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400">
                <?= esc(lang('App.remove')) ?>
            </button>
        </div>
    </div>

    <!-- No file selected: click-to-open zone -->
    <button type="button"
            x-show="fileId === ''"
            @click="openPicker()"
            class="flex w-full cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center transition-colors hover:border-brand-400 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <div class="flex flex-col items-center gap-2">
            <?= ui_icon('upload', 'h-8 w-8 text-gray-400') ?>
            <p class="text-sm text-gray-500"><?= esc($fpLabel) ?></p>
        </div>
    </button>

    <!-- Loading indicator -->
    <div x-show="loading" x-cloak class="mt-1 flex items-center gap-1 text-xs text-gray-400">
        <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <?= esc(lang('App.loading')) ?>
    </div>
</div>
