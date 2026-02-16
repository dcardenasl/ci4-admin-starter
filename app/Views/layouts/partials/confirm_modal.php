<div x-show="$store.confirm.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/30" @click="$store.confirm.close()"></div>
    <div class="relative w-full max-w-md bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900" x-text="$store.confirm.title"></h3>
        <p class="mt-2 text-sm text-gray-600" x-text="$store.confirm.message"></p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
                @click="$store.confirm.close()">
                Cancelar
            </button>
            <button type="button" class="px-4 py-2 rounded-lg bg-red-600 text-sm text-white hover:bg-red-700"
                @click="$store.confirm.accept()">
                Confirmar
            </button>
        </div>
    </div>
</div>
