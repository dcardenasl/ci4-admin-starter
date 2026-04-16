<div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-4 text-sm text-gray-600 xl:flex-row xl:items-center xl:justify-between" x-show="!loading && !error && rows.length > 0 && hasPagination()">
    <div class="flex flex-wrap items-center gap-3">
        <span x-text="paginationLabel()"></span>
        <label class="flex items-center gap-2 text-xs text-gray-500">
            <span><?= lang('App.per_page') ?></span>
            <select class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700" :value="String(query.limit || pagination.limit || 25)" @change="onLimitChange($event.target.value)" :disabled="loading">
                <template x-for="option in paginationLimitOptions()" :key="'limit-' + option">
                    <option :value="String(option)" x-text="String(option)"></option>
                </template>
            </select>
        </label>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <nav class="flex items-center gap-1">
            <template x-if="!isCursorMode()">
                <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                    x-show="pagination.current_page > 1"
                    @click="goToFirstPage()" :disabled="loading"><?= lang('App.first') ?></button>
            </template>
            <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                x-show="isCursorMode() ? (pagination.prev_cursor !== '') : (pagination.current_page > 1)"
                @click="isCursorMode() ? goToCursor(pagination.prev_cursor) : goToPage(pagination.current_page - 1)" :disabled="loading"><?= lang('App.previous') ?></button>
            <template x-if="!isCursorMode()">
                <div class="flex items-center gap-1">
                    <template x-for="page in pageWindow()" :key="'p-' + page">
                        <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50" :class="page === pagination.current_page ? 'bg-brand-600 text-white border-brand-600' : ''"
                            @click="goToPage(page)" :disabled="loading" x-text="page"></button>
                    </template>
                </div>
            </template>
            <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                x-show="isCursorMode() ? (pagination.next_cursor !== '') : (pagination.current_page < pagination.last_page)"
                @click="isCursorMode() ? goToCursor(pagination.next_cursor) : goToPage(pagination.current_page + 1)" :disabled="loading"><?= lang('App.next') ?></button>
            <template x-if="!isCursorMode()">
                <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                    x-show="pagination.current_page < pagination.last_page"
                    @click="goToLastPage()" :disabled="loading"><?= lang('App.last') ?></button>
            </template>
        </nav>
        <template x-if="!isCursorMode()">
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500"><?= lang('App.go_to_page') ?></span>
                <input type="number" min="1" :max="pagination.last_page" x-model="page_input" @keydown.enter.prevent="goToPageFromInput()"
                    class="w-20 rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700" :disabled="loading">
                <button type="button" class="rounded-lg border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50 disabled:opacity-40"
                    @click="goToPageFromInput()" :disabled="loading"><?= lang('App.go') ?></button>
            </div>
        </template>
    </div>
</div>
