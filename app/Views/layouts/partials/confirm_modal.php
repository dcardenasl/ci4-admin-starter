<div
    x-show="$store.confirm.open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    @keydown.escape.window="!$store.confirm.accepting && $store.confirm.close()"
    @keydown.tab.prevent="$store.confirm.handleTab($event, $refs.dialog)"
>
    <div class="absolute inset-0 bg-black/30" @click="!$store.confirm.accepting && $store.confirm.close()"></div>
    <div
        id="confirm-dialog-panel"
        x-ref="dialog"
        class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-dialog-title"
        tabindex="-1"
    >
        <h3 id="confirm-dialog-title" class="text-lg font-semibold text-gray-900" x-text="$store.confirm.title"></h3>
        <p class="mt-2 text-sm text-gray-600" x-text="$store.confirm.message"></p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button"
                class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="$store.confirm.accepting"
                @click="$store.confirm.close()">
                <?= lang('App.cancel') ?>
            </button>
            <button type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-red-600 text-sm text-white hover:bg-red-700 disabled:opacity-75 disabled:cursor-not-allowed"
                :disabled="$store.confirm.accepting"
                @click="$store.confirm.accept()">
                <svg x-show="$store.confirm.accepting" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                <span x-text="$store.confirm.accepting ? '<?= lang('App.loading') ?>' : '<?= lang('App.confirm') ?>'"></span>
            </button>
        </div>
    </div>
</div>
