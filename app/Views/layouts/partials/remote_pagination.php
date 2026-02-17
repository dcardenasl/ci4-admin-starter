<div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-4 text-sm text-gray-600 xl:flex-row xl:items-center xl:justify-between" x-show="!loading && !error && rows.length > 0 && hasPagination()">
    <div class="flex flex-wrap items-center gap-3">
        <span x-text="paginationLabel()"></span>
        <label class="flex items-center gap-2 text-xs text-gray-500">
            <span><?= lang('App.perPage') ?></span>
            <select class="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700" :value="String(query.limit || pagination.limit || 25)" @change="onLimitChange($event.target.value)" :disabled="loading">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </label>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <nav class="flex items-center gap-1">
            <template x-if="!isCursorMode()">
                <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                    x-show="pagination.currentPage > 1"
                    @click="goToFirstPage()" :disabled="loading"><?= lang('App.first') ?></button>
            </template>
            <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                x-show="isCursorMode() ? (pagination.prevCursor !== '') : (pagination.currentPage > 1)"
                @click="isCursorMode() ? goToCursor(pagination.prevCursor) : goToPage(pagination.currentPage - 1)" :disabled="loading"><?= lang('App.previous') ?></button>
            <template x-if="!isCursorMode()">
                <div class="flex items-center gap-1">
                    <template x-for="page in pageWindow()" :key="'p-' + page">
                        <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50" :class="page === pagination.currentPage ? 'bg-brand-600 text-white border-brand-600' : ''"
                            @click="goToPage(page)" :disabled="loading" x-text="page"></button>
                    </template>
                </div>
            </template>
            <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                x-show="isCursorMode() ? (pagination.nextCursor !== '') : (pagination.currentPage < pagination.lastPage)"
                @click="isCursorMode() ? goToCursor(pagination.nextCursor) : goToPage(pagination.currentPage + 1)" :disabled="loading"><?= lang('App.next') ?></button>
            <template x-if="!isCursorMode()">
                <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                    x-show="pagination.currentPage < pagination.lastPage"
                    @click="goToLastPage()" :disabled="loading"><?= lang('App.last') ?></button>
            </template>
        </nav>
        <template x-if="!isCursorMode()">
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500"><?= lang('App.goToPage') ?></span>
                <input type="number" min="1" :max="pagination.lastPage" x-model="pageInput" @keydown.enter.prevent="goToPageFromInput()"
                    class="w-20 rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700" :disabled="loading">
                <button type="button" class="rounded-lg border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50 disabled:opacity-40"
                    @click="goToPageFromInput()" :disabled="loading"><?= lang('App.go') ?></button>
            </div>
        </template>
    </div>
</div>
