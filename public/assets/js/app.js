const renderLucideIcons = () => {
    if (!window.lucide || typeof window.lucide.createIcons !== 'function') {
        return false;
    }

    window.lucide.createIcons({
        attrs: {
            'stroke-width': 1.8
        }
    });

    return true;
};

const bootLucideIcons = () => {
    if (renderLucideIcons()) {
        return;
    }

    let attempts = 0;
    const interval = setInterval(() => {
        attempts += 1;
        if (renderLucideIcons() || attempts >= 20) {
            clearInterval(interval);
        }
    }, 150);
};

const queryToObject = (search) => {
    const params = new URLSearchParams(search);
    const query = {};

    params.forEach((value, key) => {
        const trimmed = value.trim();
        if (trimmed !== '') {
            query[key] = trimmed;
        }
    });

    return query;
};

const objectToQueryString = (query) => {
    const params = new URLSearchParams();

    Object.entries(query || {}).forEach(([key, value]) => {
        if (typeof value === 'string' && value.trim() !== '') {
            params.append(key, value.trim());
        }
    });

    return params.toString();
};

const formToQuery = (form) => {
    const formData = new FormData(form);
    const query = {};

    formData.forEach((value, key) => {
        if (typeof value !== 'string') {
            return;
        }

        const trimmed = value.trim();
        if (trimmed !== '') {
            query[key] = trimmed;
        }
    });

    return query;
};

const isObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);

const tablePayloadRoot = (payload) => {
    if (!isObject(payload)) {
        return {};
    }

    const nested = payload.data;
    if (!isObject(nested)) {
        return payload;
    }

    if (Array.isArray(nested.data) || isObject(nested.meta) || 
        nested.currentPage !== undefined || nested.page !== undefined ||
        nested.lastPage !== undefined ||
        nested.total !== undefined || isObject(nested.summary)) {
        return nested;
    }

    return payload;
};

const statusBadgeClass = (status) => {
    const val = String(status || '').toLowerCase();

    if (['active', 'approved', 'success'].includes(val)) {
        return 'bg-green-100 text-green-800';
    }
    if (['pending', 'pending_approval', 'processing'].includes(val)) {
        return 'bg-yellow-100 text-yellow-800';
    }
    if (['suspended', 'rejected', 'failed'].includes(val)) {
        return 'bg-red-100 text-red-800';
    }

    return 'bg-gray-100 text-gray-800';
};

const roleBadgeClass = (role) => ['admin', 'superadmin'].includes(String(role || '').toLowerCase())
    ? 'bg-brand-100 text-brand-800'
    : 'bg-gray-100 text-gray-700';

const auditActionBadgeClass = (action) => {
    const val = String(action || '').toLowerCase();

    if (val === 'create') return 'bg-green-100 text-green-800';
    if (val === 'update') return 'bg-blue-100 text-blue-800';
    if (val === 'delete') return 'bg-red-100 text-red-800';
    if (val === 'login') return 'bg-brand-100 text-brand-800';
    if (val === 'logout') return 'bg-gray-100 text-gray-800';
    if (val === 'approve') return 'bg-emerald-100 text-emerald-800';

    return 'bg-gray-100 text-gray-700';
};

const localePrefix = () => String(document.documentElement?.lang || 'es').toLowerCase().startsWith('en') ? 'en' : 'es';
const localeTag = () => (localePrefix() === 'en' ? 'en-US' : 'es-ES');

const uiLabels = {
    es: {
        confirmAction: 'Confirmar accion',
        confirm: 'Confirmar',
        requestFailed: 'La solicitud fallo (HTTP {status}).',
        loadRetry: 'No se pudo cargar la informacion. Intenta nuevamente.'
    },
    en: {
        confirmAction: 'Confirm action',
        confirm: 'Confirm',
        requestFailed: 'Request failed (HTTP {status}).',
        loadRetry: 'Could not load the information. Please try again.'
    }
};

const statusLabels = {
    es: {
        active: 'Activo',
        pending: 'Pendiente',
        pending_approval: 'Pendiente de aprobacion',
        suspended: 'Suspendido',
        approved: 'Aprobado',
        rejected: 'Rechazado',
        processing: 'Procesando',
        success: 'Exitoso',
        failed: 'Fallido'
    },
    en: {
        active: 'Active',
        pending: 'Pending',
        pending_approval: 'Pending approval',
        suspended: 'Suspended',
        approved: 'Approved',
        rejected: 'Rejected',
        processing: 'Processing',
        success: 'Success',
        failed: 'Failed'
    }
};

const roleLabels = {
    es: {
        admin: 'Administrador',
        superadmin: 'Superadministrador',
        user: 'Usuario'
    },
    en: {
        admin: 'Admin',
        superadmin: 'Superadmin',
        user: 'User'
    }
};

const auditActionLabels = {
    es: {
        create: 'Crear',
        update: 'Actualizar',
        delete: 'Eliminar',
        login: 'Iniciar sesion',
        logout: 'Cerrar sesion',
        approve: 'Aprobar'
    },
    en: {
        create: 'Create',
        update: 'Update',
        delete: 'Delete',
        login: 'Login',
        logout: 'Logout',
        approve: 'Approve'
    }
};

const paginationLabels = {
    es: {
        visibleResults: 'Resultados visibles',
        showing: 'Mostrando',
        of: 'de'
    },
    en: {
        visibleResults: 'Visible results',
        showing: 'Showing',
        of: 'of'
    }
};

const statusLabel = (status) => {
    const value = String(status || '').trim();
    if (value === '') {
        return '-';
    }

    const key = value.toLowerCase();
    const locale = localePrefix();

    return statusLabels[locale]?.[key] || value;
};

const roleLabel = (role) => {
    const value = String(role || '').trim();
    if (value === '') {
        return '-';
    }

    const key = value.toLowerCase();
    const locale = localePrefix();

    return roleLabels[locale]?.[key] || value;
};

const auditActionLabel = (action) => {
    const value = String(action || '').trim();
    if (value === '') {
        return '-';
    }

    const key = value.toLowerCase();
    const locale = localePrefix();

    return auditActionLabels[locale]?.[key] || value;
};

const toDateInput = (value) => {
    if (value === null || value === undefined) {
        return null;
    }

    if (typeof value === 'string' || typeof value === 'number') {
        return value;
    }

    if (Array.isArray(value)) {
        return value.length > 0 ? toDateInput(value[0]) : null;
    }

    if (typeof value === 'object') {
        if (typeof value.date === 'string' || typeof value.date === 'number') {
            return value.date;
        }
        if (typeof value.datetime === 'string' || typeof value.datetime === 'number') {
            return value.datetime;
        }
        if (typeof value.createdAt === 'string' || typeof value.createdAt === 'number') {
            return value.createdAt;
        }
        if (typeof value.value === 'string' || typeof value.value === 'number') {
            return value.value;
        }
    }

    return null;
};

const formatDate = (value) => {
    const candidate = toDateInput(value);
    if (candidate === null || candidate === '') {
        return '-';
    }

    const date = new Date(candidate);
    if (Number.isNaN(date.getTime())) {
        return String(candidate);
    }

    return new Intl.DateTimeFormat(localeTag(), {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

document.addEventListener('alpine:init', () => {
    const locale = localePrefix();
    const text = uiLabels[locale] || uiLabels.es;

    Alpine.store('confirm', {
        open: false,
        title: text.confirmAction,
        message: '',
        onAccept: null,
        show(message, onAccept, title = text.confirmAction) {
            this.open = true;
            this.message = message;
            this.title = title;
            this.onAccept = onAccept;
        },
        close() {
            this.open = false;
            this.message = '';
            this.onAccept = null;
        },
        accept() {
            if (typeof this.onAccept === 'function') {
                this.onAccept();
            }
            this.close();
        }
    });

    Alpine.store('toast', {
        items: [],
        push(type, message) {
            const id = Date.now() + Math.random();
            this.items.push({ id, type, message });
            setTimeout(() => {
                this.remove(id);
            }, 5000);
        },
        remove(id) {
            this.items = this.items.filter((item) => item.id !== id);
        }
    });

    Alpine.data('appShell', () => ({
        sidebarOpen: window.innerWidth >= 768
    }));

    Alpine.data('remoteTable', (config = {}) => ({
        apiUrl: config.apiUrl || window.location.pathname,
        pageUrl: config.pageUrl || window.location.pathname,
        mode: config.mode || 'generic',
        routes: config.routes || {},
        csrf: config.csrf || { name: '', hash: '' },
        confirmDelete: config.confirmDelete || text.confirm,
        loading: false,
        error: false,
        errorMessage: '',
        rows: [],
        summary: {},
        pagination: {
            mode: 'page',
            currentPage: 1,
            lastPage: 1,
            total: 0,
            limit: 25,
            from: 0,
            to: 0,
            nextCursor: '',
            prevCursor: ''
        },
        pageInput: '1',
        query: {},
        filterDefaults: {},
        filterFields: new Set(),
        ignoredFilterKeys: new Set(['sort', 'page', 'cursor']),
        requestId: 0,
        debounceTimers: new WeakMap(),
        form: null,

        init() {
            this.form = this.$el.querySelector('form[data-table-filter-form="1"]');
            this.loadFilterConfig();
            const fromUrl = queryToObject(window.location.search);
            this.query = { ...this.defaultFilterQuery(), ...fromUrl };
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
                } catch (_error) {}
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
                } catch (_error) {}
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
                delete this.query.page;
                delete this.query.cursor;
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
                        delete this.query.page;
                        delete this.query.cursor;
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

            try {
                const response = await fetch(apiUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const text = await response.text();
                let payload = {};
                if (text.trim() !== '') {
                    payload = JSON.parse(text);
                }

                if (requestId !== this.requestId) {
                    return;
                }

                if (!response.ok) {
                    this.rows = [];
                    this.summary = {};
                    this.pagination = {
                        mode: 'page',
                        currentPage: 1,
                        lastPage: 1,
                        total: 0,
                        limit: 25,
                        from: 0,
                        to: 0,
                        nextCursor: '',
                        prevCursor: ''
                    };
                    this.pageInput = '1';
                    this.error = true;
                    this.errorMessage = this.resolveErrorMessage(payload, response.status);
                    return;
                }

                const root = tablePayloadRoot(payload);
                this.rows = this.extractRows(root);
                this.summary = this.extractSummary(root);
                this.pagination = this.extractPagination(root, this.rows.length);
                this.pageInput = String(this.pagination.currentPage);

                if (pushHistory) {
                    window.history.pushState({}, '', pageUrl);
                }
            } catch (_error) {
                if (requestId !== this.requestId) {
                    return;
                }
                this.rows = [];
                this.summary = {};
                this.error = true;
                this.errorMessage = text.loadRetry;
                this.pageInput = '1';
            } finally {
                if (requestId === this.requestId) {
                    this.loading = false;
                }
            }
        },

        extractRows(root) {
            if (Array.isArray(root.data)) {
                return root.data;
            }

            if (isObject(root.data) && Array.isArray(root.data.data)) {
                return root.data.data;
            }

            if (Array.isArray(root.items)) {
                return root.items;
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
            const nextCursor = String(meta.nextCursor ?? root.nextCursor ?? '');
            const prevCursor = String(meta.prevCursor ?? root.prevCursor ?? '');
            const hasCursor = nextCursor !== '' || prevCursor !== '' || String(this.query.cursor || '') !== '';
            
            const limit = Number(meta.perPage ?? root.perPage ?? meta.limit ?? this.query.limit ?? 25) || 25;
            const safeLimit = Math.max(1, limit);
            
            const total = Number(meta.total ?? root.total ?? meta.totalEstimate ?? visibleCount) || visibleCount;
            
            const currentPage = Number(meta.page ?? meta.currentPage ?? root.page ?? root.currentPage ?? this.query.page ?? 1) || 1;
            
            const derivedLastPage = Math.max(1, Math.ceil(Math.max(0, total) / safeLimit));
            const lastPage = Number(meta.lastPage ?? root.lastPage ?? derivedLastPage) || derivedLastPage;
            
            const normalizedCurrentPage = Math.max(1, Math.min(currentPage, Math.max(1, lastPage)));
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
                currentPage: normalizedCurrentPage,
                lastPage: Math.max(1, lastPage),
                total: Math.max(0, total),
                limit: safeLimit,
                from: Math.max(0, from),
                to: Math.max(0, to),
                nextCursor,
                prevCursor
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
                return this.pagination.prevCursor !== '' || this.pagination.nextCursor !== '';
            }

            return this.pagination.lastPage > 1;
        },

        pageWindow() {
            const start = Math.max(1, this.pagination.currentPage - 2);
            const end = Math.min(this.pagination.lastPage, this.pagination.currentPage + 2);
            const pages = [];
            for (let page = start; page <= end; page += 1) {
                pages.push(page);
            }

            return pages;
        },

        paginationLabel() {
            const locale = localePrefix();
            const labels = paginationLabels[locale] || paginationLabels.es;
            if (this.isCursorMode()) {
                return `${labels.visibleResults}: ${this.pagination.total}`;
            }

            if (this.pagination.total <= 0 || this.pagination.from <= 0) {
                return `${labels.showing} 0 ${labels.of} ${this.pagination.total}`;
            }

            return `${labels.showing} ${this.pagination.from}-${this.pagination.to} ${labels.of} ${this.pagination.total}`;
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
                delete this.query.sort;
            } else {
                this.query.sort = field;
            }

            delete this.query.page;
            delete this.query.cursor;
            this.fetchData(true);
        },

        goToPage(page) {
            const boundedPage = Math.max(1, Math.min(this.pagination.lastPage || 1, page));
            this.query.page = String(boundedPage);
            delete this.query.cursor;
            this.pageInput = String(boundedPage);
            this.fetchData(true);
        },

        goToFirstPage() {
            if (this.isCursorMode() || this.pagination.currentPage <= 1) {
                return;
            }
            this.goToPage(1);
        },

        goToLastPage() {
            if (this.isCursorMode() || this.pagination.currentPage >= this.pagination.lastPage) {
                return;
            }
            this.goToPage(this.pagination.lastPage);
        },

        goToPageFromInput() {
            if (this.isCursorMode()) {
                return;
            }

            const page = Number.parseInt(String(this.pageInput || ''), 10);
            if (!Number.isFinite(page) || page <= 0) {
                this.pageInput = String(this.pagination.currentPage);
                return;
            }

            this.goToPage(page);
        },

        goToCursor(cursor) {
            if (!cursor) {
                return;
            }
            this.query.cursor = String(cursor);
            delete this.query.page;
            this.fetchData(true);
        },

        onLimitChange(limit) {
            const parsed = Number.parseInt(String(limit || ''), 10);
            if (!Number.isFinite(parsed) || parsed <= 0) {
                delete this.query.limit;
            } else {
                this.query.limit = String(Math.min(100, Math.max(1, parsed)));
            }

            delete this.query.page;
            delete this.query.cursor;
            this.pageInput = '1';
            this.fetchData(true);
        },

        fullName(row) {
            const firstName = String(row.firstName ?? '').trim();
            const lastName = String(row.lastName ?? '').trim();
            const fullName = `${firstName} ${lastName}`.trim();

            return fullName === '' ? '-' : fullName;
        },

        statusBadgeClass,
        statusLabel,
        roleLabel,
        roleBadgeClass,
        auditActionBadgeClass,
        auditActionLabel,
        formatDate,

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
        }
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    bootLucideIcons();
});

window.addEventListener('load', () => {
    bootLucideIcons();
});
