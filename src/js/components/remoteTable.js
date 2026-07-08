import { queryToObject, objectToQueryString, formToQuery, isObject, tablePayloadRoot } from '../utils/url.js';
import { localePrefix, uiLabels, paginationLabels } from '../utils/labels.js';
import { statusBadgeClass, auditActionBadgeClass, auditResultBadgeClass, auditSeverityBadgeClass } from '../utils/badges.js';
import { statusLabel, auditActionLabel, auditResultLabel, auditSeverityLabel } from '../utils/labels.js';
import { formatDate } from '../utils/date.js';
import { bootLucideIcons } from '../utils/lucide.js';
import { devError } from '../utils/dev.js';

/**
 * Server-driven data table component.
 * Fetches rows from `config.apiUrl`, manages sort/filter/pagination state,
 * and pushes URL history via `config.pageUrl`.
 *
 * @param {{ apiUrl?: string, pageUrl?: string, mode?: string, routes?: object, csrf?: { name: string, hash: string }, defaultSort?: string, limitOptions?: string[], confirmDelete?: string }} config
 * @returns {object} Alpine.js component data object
 */
export const remoteTableFactory = (config = {}) => {
    const text = uiLabels[localePrefix()] || uiLabels.es;

    return {
        apiUrl: config.apiUrl || window.location.pathname,
        pageUrl: config.pageUrl || window.location.pathname,
        mode: config.mode || 'generic',
        routes: config.routes || {},
        csrf: config.csrf || { name: '', hash: '' },
        defaultSort: typeof config.defaultSort === 'string' ? config.defaultSort : '',
        limitOptions: Array.isArray(config.limitOptions) && config.limitOptions.length > 0 ? config.limitOptions : ['10', '25', '50', '100'],
        confirmDelete: config.confirmDelete || text.confirm,
        loading: false,
        error: false,
        errorMessage: '',
        rows: [],
        summary: {},
        pagination: {
            mode: 'page',
            current_page: 1,
            last_page: 1,
            total_items: 0,
            limit: 25,
            from: 0,
            to: 0,
            next_cursor: '',
            prev_cursor: ''
        },
        page_input: '1',
        query: {},
        filterDefaults: {},
        filterFields: new Set(),
        ignoredFilterKeys: new Set(['sort', 'page', 'cursor', 'order_by', 'order_dir', 'per_page', 'limit']),
        requestId: 0,
        debounceTimers: new WeakMap(),
        form: null,

        init() {
            this.form = this.$el.querySelector('form[data-table-filter-form="1"]');
            this.loadFilterConfig();
            const fromUrl = queryToObject(window.location.search);
            this.query = { ...this.defaultFilterQuery(), ...fromUrl };
            if (typeof this.query.sort !== 'string' || this.query.sort.trim() === '') {
                const defaultSort = String(this.defaultSort || '').trim();
                if (defaultSort !== '') {
                    this.query.sort = defaultSort;
                }
            }
            this.applyQueryToForm();
            this.bindFormEvents();
            this.fetchData(false);
            window.addEventListener('popstate', () => {
                this.query = { ...this.defaultFilterQuery(), ...queryToObject(window.location.search) };
                this.applyQueryToForm();
                this.fetchData(false);
            });
        },

        loadFilterConfig() {
            this.filterDefaults = {};
            this.filterFields = new Set();
            this.ignoredFilterKeys = new Set(['sort', 'page', 'cursor']);

            if (!this.form) {
                return;
            }

            const fieldElements = this.form.querySelectorAll('input[name], select[name], textarea[name]');
            fieldElements.forEach((el) => {
                const name = el.getAttribute('name');
                if (!name) {
                    return;
                }
                this.filterFields.add(name);
                if (this.filterDefaults[name] === undefined) {
                    this.filterDefaults[name] = '';
                }
            });

            const defaultsRaw = String(this.form.dataset.filterDefaults || '').trim();
            if (defaultsRaw !== '') {
                try {
                    const parsed = JSON.parse(defaultsRaw);
                    if (isObject(parsed)) {
                        Object.entries(parsed).forEach(([key, value]) => {
                            if (typeof key !== 'string' || key.trim() === '') {
                                return;
                            }
                            this.filterFields.add(key);
                            this.filterDefaults[key] = String(value ?? '').trim();
                        });
                    }
                } catch {
                    return;
                }
            }

            const ignoredRaw = String(this.form.dataset.filterIgnored || '').trim();
            if (ignoredRaw !== '') {
                try {
                    const parsed = JSON.parse(ignoredRaw);
                    if (Array.isArray(parsed)) {
                        parsed.forEach((key) => {
                            if (typeof key === 'string' && key.trim() !== '') {
                                this.ignoredFilterKeys.add(key);
                            }
                        });
                    }
                } catch {
                    return;
                }
            }
        },

        hasActiveFilters() {
            if (!this.form || this.form.dataset.reactiveHasFilters !== '1') {
                return false;
            }

            const keys = new Set([
                ...Array.from(this.filterFields),
                ...Object.keys(this.query || {})
            ]);

            for (const key of keys) {
                if (this.ignoredFilterKeys.has(key)) {
                    continue;
                }
                if (!this.filterFields.has(key) && this.filterDefaults[key] === undefined) {
                    continue;
                }

                const defaultValue = String(this.filterDefaults[key] ?? '').trim();
                const currentValue = Object.prototype.hasOwnProperty.call(this.query, key)
                    ? String(this.query[key] ?? '').trim()
                    : '';

                if (currentValue !== defaultValue) {
                    return true;
                }
            }

            return false;
        },

        defaultFilterQuery() {
            const query = {};

            Object.entries(this.filterDefaults || {}).forEach(([key, value]) => {
                const normalized = String(value ?? '').trim();
                if (normalized !== '') {
                    query[key] = normalized;
                }
            });

            return query;
        },

        bindFormEvents() {
            if (!this.form) {
                return;
            }

            this.form.addEventListener('submit', (event) => {
                event.preventDefault();
                const activeSort = typeof this.query.sort === 'string' ? this.query.sort : '';
                this.query = formToQuery(this.form);
                if (activeSort !== '') {
                    this.query.sort = activeSort;
                }
                this.query.page = '';
                this.query.cursor = '';
                this.fetchData(true);
            });

            this.form.querySelectorAll('[data-table-debounce]').forEach((input) => {
                input.addEventListener('input', () => {
                    const previousTimer = this.debounceTimers.get(input);
                    if (previousTimer) {
                        clearTimeout(previousTimer);
                    }

                    const wait = Number.parseInt(input.dataset.tableDebounce || '350', 10);
                    const timer = setTimeout(() => {
                        const activeSort = typeof this.query.sort === 'string' ? this.query.sort : '';
                        this.query = formToQuery(this.form);
                        if (activeSort !== '') {
                            this.query.sort = activeSort;
                        }
                        this.query.page = '';
                        this.query.cursor = '';
                        this.fetchData(true);
                    }, Number.isFinite(wait) ? wait : 350);
                    this.debounceTimers.set(input, timer);
                });
            });
        },

        applyQueryToForm() {
            if (!this.form) {
                return;
            }

            const elements = this.form.querySelectorAll('input[name], select[name], textarea[name]');
            elements.forEach((el) => {
                const name = el.getAttribute('name');
                if (!name) {
                    return;
                }
                const value = this.query[name] ?? '';
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = String(el.value) === value;
                } else {
                    el.value = value;
                }
            });
        },

        buildUrl(base, query) {
            const url = new URL(base, window.location.origin);
            const qs = objectToQueryString(query);
            url.search = qs;

            return url.toString();
        },

        async fetchData(pushHistory = true) {
            this.loading = true;
            this.error = false;
            this.errorMessage = '';
            this.requestId += 1;
            const requestId = this.requestId;

            const apiUrl = this.buildUrl(this.apiUrl, this.query);
            const pageUrl = this.buildUrl(this.pageUrl, this.query);

            const fetchLocale = localePrefix();
            const pageText = uiLabels[fetchLocale] || uiLabels.es;

            try {
                const response = await fetch(apiUrl, {
                    credentials: 'include',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const rawBody = await response.text();
                let payload = {};

                if (rawBody.trim() !== '') {
                    try {
                        payload = JSON.parse(rawBody);
                    } catch (e) {
                        devError('JSON Parse error in fetchData:', e);
                        if (requestId === this.requestId) {
                            this.rows = [];
                            this.error = true;
                            this.errorMessage = pageText.loadRetry;
                        }
                        return;
                    }
                }

                if (requestId !== this.requestId) {
                    return;
                }

                if (!response.ok) {
                    this.rows = [];
                    this.summary = {};
                    this.pagination = {
                        mode: 'page',
                        current_page: 1,
                        last_page: 1,
                        total_items: 0,
                        limit: 25,
                        from: 0,
                        to: 0,
                        next_cursor: '',
                        prev_cursor: ''
                    };
                    this.page_input = '1';
                    this.error = true;
                    this.errorMessage = this.resolveErrorMessage(payload, response.status);

                    return;
                }

                const root = tablePayloadRoot(payload);
                this.rows = this.extractRows(root);
                this.summary = this.extractSummary(root);
                this.pagination = this.extractPagination(root, this.rows.length);
                this.page_input = String(this.pagination.current_page);

                this.$nextTick(() => {
                    bootLucideIcons();
                });

                if (pushHistory) {
                    window.history.pushState({}, '', pageUrl);
                }
            } catch (err) {
                devError('Fetch error in fetchData:', err);
                if (requestId !== this.requestId) {
                    return;
                }
                this.rows = [];
                this.summary = {};
                this.error = true;
                this.errorMessage = pageText.loadRetry;
                this.page_input = '1';
            } finally {
                if (requestId === this.requestId) {
                    this.loading = false;
                }
            }
        },

        extractRows(root) {
            // Priority 1: Direct root.data array
            if (Array.isArray(root.data)) {
                return root.data;
            }

            // Priority 2: root.data.data array (paginated wrapper)
            if (isObject(root.data) && Array.isArray(root.data.data)) {
                return root.data.data;
            }

            // Priority 3: root.items array
            if (Array.isArray(root.items)) {
                return root.items;
            }

            // Priority 4: Any array under common keys (users, files, audit, api_keys)
            const commonKeys = ['users', 'files', 'audit', 'api_keys', 'keys', 'logs', 'entries'];
            for (const key of commonKeys) {
                if (Array.isArray(root[key])) {
                    return root[key];
                }
                // Also check inside .data if that exists as object
                if (isObject(root.data) && Array.isArray(root.data[key])) {
                    return root.data[key];
                }
            }

            return [];
        },

        extractSummary(root) {
            if (isObject(root.summary)) {
                return root.summary;
            }

            if (isObject(root.data) && isObject(root.data.summary)) {
                return root.data.summary;
            }

            return {};
        },

        extractPagination(root, visibleCount) {
            const meta = isObject(root.meta) ? root.meta : {};
            const next_cursor = String(meta.next_cursor ?? root.next_cursor ?? '');
            const prev_cursor = String(meta.prev_cursor ?? root.prev_cursor ?? '');
            const hasCursor = next_cursor !== '' || prev_cursor !== '' || String(this.query.cursor || '') !== '';

            const limit = Number(meta.per_page ?? meta.limit ?? root.per_page ?? root.limit ?? this.query.limit ?? this.query.per_page ?? 25) || 25;
            const safeLimit = Math.max(1, limit);

            const total = Number(meta.total_items ?? meta.total ?? root.total_items ?? root.total ?? visibleCount) || visibleCount;

            const current_page = Number(meta.current_page ?? meta.page ?? root.current_page ?? root.page ?? this.query.page ?? 1) || 1;

            const derivedLastPage = Math.max(1, Math.ceil(Math.max(0, total) / safeLimit));
            const last_page = Number(meta.last_page ?? root.last_page ?? derivedLastPage) || derivedLastPage;

            const normalizedCurrentPage = Math.max(1, Math.min(current_page, Math.max(1, last_page)));
            const from = total <= 0 ? 0 : ((normalizedCurrentPage - 1) * safeLimit) + 1;
            let to = 0;
            if (total > 0) {
                if (visibleCount > 0) {
                    to = Math.min(total, from + visibleCount - 1);
                } else {
                    to = Math.min(total, normalizedCurrentPage * safeLimit);
                }
            }

            return {
                mode: hasCursor ? 'cursor' : 'page',
                current_page: normalizedCurrentPage,
                last_page: Math.max(1, last_page),
                total_items: Math.max(0, total),
                limit: safeLimit,
                from: Math.max(0, from),
                to: Math.max(0, to),
                next_cursor,
                prev_cursor
            };
        },

        resolveErrorMessage(payload, status) {
            if (isObject(payload)) {
                if (typeof payload.message === 'string' && payload.message.trim() !== '') {
                    return payload.message;
                }

                if (Array.isArray(payload.messages) && payload.messages.length > 0) {
                    return String(payload.messages[0]);
                }
            }

            return text.requestFailed.replace('{status}', String(status));
        },

        isCursorMode() {
            return this.pagination.mode === 'cursor';
        },

        hasPagination() {
            if (this.isCursorMode()) {
                return this.pagination.prev_cursor !== '' || this.pagination.next_cursor !== '';
            }

            return this.pagination.last_page > 1;
        },

        pageWindow() {
            const start = Math.max(1, this.pagination.current_page - 2);
            const end = Math.min(this.pagination.last_page, this.pagination.current_page + 2);
            const pages = [];
            for (let page = start; page <= end; page += 1) {
                pages.push(page);
            }

            return pages;
        },

        paginationLabel() {
            const currentLocale = localePrefix();
            const labels = paginationLabels[currentLocale] || paginationLabels.es;
            if (this.isCursorMode()) {
                return `${labels.visibleResults}: ${this.pagination.total_items}`;
            }

            if (this.pagination.total_items <= 0 || this.pagination.from <= 0) {
                return `${labels.showing} 0 ${labels.of} ${this.pagination.total_items}`;
            }

            return `${labels.showing} ${this.pagination.from}-${this.pagination.to} ${labels.of} ${this.pagination.total_items}`;
        },

        paginationLimitOptions() {
            const options = [];
            this.limitOptions.forEach((value) => {
                const parsed = Number.parseInt(String(value ?? ''), 10);
                if (!Number.isFinite(parsed) || parsed <= 0) {
                    return;
                }
                options.push(parsed);
            });

            if (options.length === 0) {
                return [10, 25, 50, 100];
            }

            return Array.from(new Set(options)).sort((a, b) => a - b);
        },

        currentSort(field) {
            const sort = String(this.query.sort || '');
            if (sort === field) {
                return 'asc';
            }
            if (sort === `-${field}`) {
                return 'desc';
            }

            return '';
        },

        sortAria(field) {
            const direction = this.currentSort(field);
            if (direction === 'asc') {
                return 'ascending';
            }
            if (direction === 'desc') {
                return 'descending';
            }

            return 'none';
        },

        sortIcon(field) {
            const direction = this.currentSort(field);
            if (direction === 'asc') {
                return '↑';
            }
            if (direction === 'desc') {
                return '↓';
            }

            return '↕';
        },

        toggleSort(field) {
            const current = this.currentSort(field);
            if (current === 'asc') {
                this.query.sort = `-${field}`;
            } else if (current === 'desc') {
                this.query.sort = '';
            } else {
                this.query.sort = field;
            }

            this.query.page = '';
            this.query.cursor = '';
            this.fetchData(true);
        },

        goToPage(page) {
            const boundedPage = Math.max(1, Math.min(this.pagination.last_page || 1, page));
            this.query.page = String(boundedPage);
            this.query.cursor = '';
            this.page_input = String(boundedPage);
            this.fetchData(true);
        },

        goToFirstPage() {
            if (this.isCursorMode() || this.pagination.current_page <= 1) {
                return;
            }
            this.goToPage(1);
        },

        goToLastPage() {
            if (this.isCursorMode() || this.pagination.current_page >= this.pagination.last_page) {
                return;
            }
            this.goToPage(this.pagination.last_page);
        },

        goToPageFromInput() {
            if (this.isCursorMode()) {
                return;
            }

            const page = Number.parseInt(String(this.page_input || ''), 10);
            if (!Number.isFinite(page) || page <= 0) {
                this.page_input = String(this.pagination.current_page);
                return;
            }

            this.goToPage(page);
        },

        goToCursor(cursor) {
            if (!cursor) {
                return;
            }
            this.query.cursor = String(cursor);
            this.query.page = '';
            this.fetchData(true);
        },

        onLimitChange(limit) {
            const parsed = Number.parseInt(String(limit || ''), 10);
            if (!Number.isFinite(parsed) || parsed <= 0) {
                this.query.limit = '';
            } else {
                const maxOption = Math.max(...this.paginationLimitOptions());
                this.query.limit = String(Math.min(maxOption, Math.max(1, parsed)));
            }

            this.query.page = '';
            this.query.cursor = '';
            this.page_input = '1';
            this.fetchData(true);
        },

        fullName(row) {
            const first_name = String(row.first_name ?? '').trim();
            const last_name = String(row.last_name ?? '').trim();
            const fullName = `${first_name} ${last_name}`.trim();

            return fullName === '' ? '-' : fullName;
        },

        statusBadgeClass,
        statusLabel,
        auditActionBadgeClass,
        auditActionLabel,
        auditResultBadgeClass,
        auditResultLabel,
        auditSeverityBadgeClass,
        auditSeverityLabel,
        formatDate,

        showUrl(id) {
            return `${this.routes.showBase}/${encodeURIComponent(String(id ?? ''))}`;
        },

        editUrl(id) {
            return `${this.routes.editBase}/${encodeURIComponent(String(id ?? ''))}/edit`;
        },

        userShowUrl(id) {
            return `${this.routes.showBase}/${encodeURIComponent(String(id ?? ''))}`;
        },

        userEditUrl(id) {
            return `${this.routes.editBase}/${encodeURIComponent(String(id ?? ''))}/edit`;
        },

        auditShowUrl(id) {
            return `${this.routes.showBase}/${encodeURIComponent(String(id ?? ''))}`;
        },

        fileDownloadUrl(id) {
            return `${this.routes.downloadBase}/${encodeURIComponent(String(id ?? ''))}/download`;
        },

        fileDeleteUrl(id) {
            return `${this.routes.deleteBase}/${encodeURIComponent(String(id ?? ''))}/delete`;
        },

        apiKeyShowUrl(id) {
            return `${this.routes.showBase}/${encodeURIComponent(String(id ?? ''))}`;
        },

        apiKeyEditUrl(id) {
            return `${this.routes.editBase}/${encodeURIComponent(String(id ?? ''))}/edit`;
        }
    };
};
