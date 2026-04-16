<div
    x-cloak
    aria-live="polite"
    aria-atomic="false"
    class="pointer-events-none fixed right-4 top-4 z-[60] flex w-full max-w-sm flex-col gap-3"
>
    <template x-for="item in $store.toast.items" :key="item.id">
        <div
            class="pointer-events-auto rounded-xl border px-4 py-3 shadow-lg transition"
            :class="{
                'border-green-200 bg-green-50 text-green-800': item.type === 'success',
                'border-red-200 bg-red-50 text-red-800': item.type === 'error',
                'border-yellow-200 bg-yellow-50 text-yellow-800': item.type === 'warning',
                'border-gray-200 bg-white text-gray-800': !['success', 'error', 'warning'].includes(item.type)
            }"
        >
            <div class="flex items-start gap-3">
                <p class="flex-1 text-sm font-medium" x-text="item.message"></p>
                <button type="button" class="rounded-md p-1 hover:bg-black/5" @click="$store.toast.remove(item.id)" aria-label="<?= esc(lang('App.close')) ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </template>
</div>
