<?php
/**
 * Global file picker modal.
 * Included once in layouts/app.php.
 * Driven by $store.filePicker — no PHP data needed.
 */
?>
<div
    x-show="$store.filePicker?.open || false"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    @keydown.escape.window="$store.filePicker?.close?.()"
>
    <div class="absolute inset-0 bg-black/40" @click="$store.filePicker?.close?.()"></div>

    <div
        id="file-picker-panel"
        data-data-url="<?= site_url('files/picker-data') ?>"
        data-upload-url="<?= site_url('files/upload') ?>"
        data-csrf-name="<?= esc(csrf_token()) ?>"
        data-csrf-hash="<?= esc(csrf_hash()) ?>"
        class="relative flex flex-col w-full max-w-4xl h-[90vh] rounded-xl border border-gray-200 bg-white shadow-xl overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-label="<?= esc(lang('Files.picker_title')) ?>"
        tabindex="-1"
        @click.stop
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Files.picker_title')) ?></h3>
            <button type="button"
                    @click="$store.filePicker.close()"
                    class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <?= ui_icon('x', 'h-5 w-5') ?>
                <span class="sr-only"><?= esc(lang('App.close')) ?></span>
            </button>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 px-6 flex-shrink-0">
            <button type="button"
                    @click="$store.filePicker.switchTab('library')"
                    class="mr-4 py-3 text-sm font-medium border-b-2 transition-colors focus:outline-none"
                    :class="$store.filePicker.activeTab === 'library'
                        ? 'border-brand-600 text-brand-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'">
                <?= esc(lang('Files.picker_tab_library')) ?>
            </button>
            <button type="button"
                    @click="$store.filePicker.switchTab('upload')"
                    class="py-3 text-sm font-medium border-b-2 transition-colors focus:outline-none"
                    :class="$store.filePicker.activeTab === 'upload'
                        ? 'border-brand-600 text-brand-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'">
                <?= esc(lang('Files.picker_tab_upload')) ?>
            </button>
        </div>

        <!-- Library tab -->
        <div x-show="$store.filePicker.activeTab === 'library'"
             class="flex flex-col flex-1 min-h-0 overflow-hidden">

            <!-- Search + size slider -->
            <div class="flex items-center gap-3 px-6 py-3 border-b border-gray-100 flex-shrink-0">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <?= ui_icon('search', 'h-4 w-4') ?>
                    </div>
                    <input type="search"
                           class="w-full rounded-lg border border-gray-300 pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                           placeholder="<?= esc(lang('Files.search_placeholder')) ?>"
                           :value="$store.filePicker.search"
                           @input="$store.filePicker.setSearch($event.target.value)">
                </div>
                <span class="text-xs text-gray-400 flex-shrink-0 tabular-nums"
                      x-text="$store.filePicker.pagination.total_items + ' <?= esc(lang('Files.picker_files')) ?>'">
                </span>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <?= ui_icon('layout-grid', 'h-3 w-3 text-gray-400') ?>
                    <input type="range" min="80" max="200" step="8"
                           :value="$store.filePicker.thumbSize"
                           @input="$store.filePicker.thumbSize = Number($event.target.value)"
                           class="w-20 h-1.5 accent-brand-600 cursor-pointer">
                    <?= ui_icon('image', 'h-4 w-4 text-gray-400') ?>
                </div>
            </div>

            <!-- Type filter pills -->
            <div x-show="$store.filePicker.showFilterTabs"
                 class="flex items-center gap-1.5 px-6 py-2.5 border-b border-gray-100 flex-shrink-0 flex-wrap">
                <template x-for="tab in [
                    { type: '', label: '<?= esc(lang('Files.category_all')) ?>' },
                    { type: 'image', label: '<?= esc(lang('Files.category_image')) ?>' },
                    { type: 'document', label: '<?= esc(lang('Files.category_document')) ?>' },
                    { type: 'video', label: '<?= esc(lang('Files.category_video')) ?>' },
                    { type: 'audio', label: '<?= esc(lang('Files.category_audio')) ?>' }
                ]" :key="tab.type">
                    <button type="button"
                            @click="$store.filePicker.setFilterType(tab.type)"
                            :class="$store.filePicker.filterType === tab.type
                                ? 'bg-brand-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="rounded-full px-3 py-1 text-xs font-medium transition-colors focus:outline-none">
                        <span x-text="tab.label"></span>
                    </button>
                </template>
            </div>

            <!-- File grid -->
            <div class="flex-1 overflow-y-auto p-5">
                <template x-if="$store.filePicker.loading">
                    <div class="flex items-center justify-center py-16 text-sm text-gray-500">
                        <svg class="animate-spin h-5 w-5 mr-2 text-brand-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <?= esc(lang('App.loading')) ?>...
                    </div>
                </template>

                <template x-if="!$store.filePicker.loading && $store.filePicker.error">
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                         x-text="$store.filePicker.errorMessage"></div>
                </template>

                <template x-if="!$store.filePicker.loading && !$store.filePicker.error && $store.filePicker.files.length === 0">
                    <div class="flex flex-col items-center justify-center py-16 text-center text-sm text-gray-500">
                        <?= ui_icon('folder-open', 'h-10 w-10 text-gray-300 mb-3') ?>
                        <p><?= esc(lang('Files.picker_empty')) ?></p>
                        <button type="button"
                                @click="$store.filePicker.switchTab('upload')"
                                class="mt-3 text-brand-600 hover:text-brand-700 text-sm">
                            <?= esc(lang('Files.picker_upload_first')) ?>
                        </button>
                    </div>
                </template>

                <template x-if="!$store.filePicker.loading && !$store.filePicker.error && $store.filePicker.files.length > 0">
                    <div :style="`grid-template-columns: repeat(auto-fill, minmax(${$store.filePicker.thumbSize}px, 1fr))`"
                         class="grid gap-3">
                        <template x-for="file in $store.filePicker.files" :key="String(file.id)">
                            <button type="button"
                                    @click="$store.filePicker.select(file)"
                                    class="group relative flex flex-col rounded-xl border border-gray-200 bg-white p-2 hover:border-brand-400 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors text-left"
                                    :class="$store.filePicker.multiSelect && $store.filePicker.isSelected(file) ? 'border-brand-500 bg-brand-50' : ''">

                                <div class="relative w-full overflow-hidden rounded-lg border border-gray-100" style="aspect-ratio:1/1">
                                    <template x-if="file.is_image">
                                        <img :src="'<?= site_url('files') ?>/' + file.id + '/view'"
                                             :alt="file.original_name"
                                             class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!file.is_image">
                                        <div class="w-full h-full flex items-center justify-center bg-gray-50">
                                            <?= ui_icon('file', 'h-8 w-8 text-gray-400') ?>
                                        </div>
                                    </template>
                                    <template x-if="file.category">
                                        <span class="absolute bottom-1 left-1 rounded bg-black/50 px-1 py-0.5 text-[10px] font-medium text-white leading-none"
                                              x-text="file.category"></span>
                                    </template>
                                </div>

                                <p class="mt-1.5 w-full text-xs text-gray-600 truncate leading-tight"
                                   x-text="file.original_name" :title="file.original_name"></p>
                                <p class="w-full text-xs text-gray-400 truncate" x-text="file.human_size"></p>

                                <!-- Selection check -->
                                <div class="absolute top-1.5 right-1.5 transition-opacity"
                                     :class="$store.filePicker.multiSelect && $store.filePicker.isSelected(file)
                                                ? 'opacity-100'
                                                : 'opacity-0 group-hover:opacity-100'">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-white"
                                          :class="$store.filePicker.multiSelect && $store.filePicker.isSelected(file) ? 'bg-green-600' : 'bg-brand-600'">
                                        <?= ui_icon('check', 'h-3 w-3') ?>
                                    </span>
                                </div>
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Multi-select confirm bar -->
            <div x-show="$store.filePicker.multiSelect && $store.filePicker.selected.length > 0"
                 x-cloak
                 class="flex items-center justify-between border-t border-gray-100 px-5 py-3 flex-shrink-0 bg-brand-50">
                <span class="text-sm font-medium text-brand-700"
                      x-text="`<?= esc(lang('Files.bulk_selected', ['___COUNT___'])) ?>`.replace('___COUNT___', $store.filePicker.selected.length)"></span>
                <button type="button"
                        @click="$store.filePicker.confirm()"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    <?= ui_icon('check', 'h-4 w-4') ?>
                    <?= esc(lang('App.confirm')) ?>
                </button>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 flex-shrink-0 text-xs text-gray-500"
                 x-show="!$store.filePicker.loading && $store.filePicker.pagination.last_page > 1">
                <button type="button"
                        @click="$store.filePicker.changePage($store.filePicker.pagination.current_page - 1)"
                        :disabled="$store.filePicker.pagination.current_page <= 1"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    <?= esc(lang('App.previous')) ?>
                </button>
                <span class="tabular-nums">
                    <span x-text="$store.filePicker.pagination.current_page"></span>
                    /
                    <span x-text="$store.filePicker.pagination.last_page"></span>
                </span>
                <button type="button"
                        @click="$store.filePicker.changePage($store.filePicker.pagination.current_page + 1)"
                        :disabled="$store.filePicker.pagination.current_page >= $store.filePicker.pagination.last_page"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    <?= esc(lang('App.next')) ?>
                </button>
            </div>
        </div>

        <!-- Upload tab -->
        <div x-show="$store.filePicker.activeTab === 'upload'"
             class="flex-1 overflow-y-auto p-5">

            <label class="block rounded-xl border-2 border-dashed p-8 text-center cursor-pointer transition-colors"
                   :class="$store.filePicker.dragging
                        ? 'border-brand-400 bg-brand-50'
                        : ($store.filePicker.uploadFileName !== ''
                            ? 'border-green-400 bg-green-50'
                            : 'border-gray-300 bg-gray-50')"
                   @dragover.prevent="$store.filePicker.dragging = true"
                   @dragleave.prevent="$store.filePicker.dragging = false"
                   @drop.prevent="$store.filePicker.dragging = false; $store.filePicker.onUploadFileChange({target: {files: $event.dataTransfer.files}})">

                <input type="file"
                       class="hidden"
                       :accept="$store.filePicker.inputAccept"
                       @change="$store.filePicker.onUploadFileChange($event)"
                       :disabled="$store.filePicker.uploading">

                <template x-if="!$store.filePicker.uploading">
                    <div class="flex flex-col items-center gap-2">
                        <?= ui_icon('upload-cloud', 'h-8 w-8 text-gray-400') ?>
                        <p class="text-sm text-gray-700" x-show="!$store.filePicker.uploadFileName">
                            <?= esc(lang('Files.drag_drop')) ?>
                        </p>
                        <p class="text-sm font-medium text-gray-800"
                           x-show="$store.filePicker.uploadFileName !== ''"
                           x-text="$store.filePicker.uploadFileName"></p>
                        <p class="text-xs text-green-700" x-show="$store.filePicker.uploadFileName !== ''">
                            <?= esc(lang('Files.file_ready')) ?>
                        </p>
                    </div>
                </template>

                <template x-if="$store.filePicker.uploading">
                    <div class="w-full max-w-xs mx-auto space-y-2">
                        <div class="flex justify-between text-xs font-medium text-gray-600">
                            <span x-text="$store.filePicker.uploadFileName"></span>
                            <span x-text="$store.filePicker.uploadProgress + '%'"></span>
                        </div>
                        <progress class="h-2 w-full overflow-hidden rounded-full"
                                  max="100" :value="$store.filePicker.uploadProgress"></progress>
                        <p class="text-xs text-gray-500 animate-pulse text-center"><?= esc(lang('App.loading')) ?>...</p>
                    </div>
                </template>
            </label>

            <p class="mt-2 text-sm text-red-600"
               x-show="$store.filePicker.uploadError !== ''"
               x-text="$store.filePicker.uploadError"></p>

            <div class="mt-4 flex justify-end">
                <button type="button"
                        @click="$store.filePicker.submitUpload()"
                        :disabled="$store.filePicker.uploading || !$store.filePicker.uploadFileName"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <template x-if="!$store.filePicker.uploading">
                        <span class="inline-flex items-center gap-2">
                            <?= ui_icon('upload', 'h-4 w-4') ?>
                            <?= esc(lang('Files.upload_button')) ?>
                        </span>
                    </template>
                    <template x-if="$store.filePicker.uploading">
                        <span class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <?= esc(lang('App.loading')) ?>...
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>
