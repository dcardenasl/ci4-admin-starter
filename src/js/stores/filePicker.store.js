import { devError } from '../utils/dev.js';
import { isObject } from '../utils/url.js';
import { uiLabels, localePrefix } from '../utils/labels.js';

/**
 * Global file picker store.
 * Call `$store.filePicker.show(options)` to open the modal.
 *
 * Options:
 *   - onSelect(file)       — called with the selected file object (single mode)
 *   - onSelectMulti(files) — called with array of selected files (multi mode)
 *   - multi: bool          — enable multi-select mode
 *   - accept: string       — MIME filter hint for upload tab
 *   - filterType: string   — pre-select a category filter ('image', 'document', …)
 */
export const filePickerStore = {
    open: false,
    activeTab: 'library',
    files: [],
    loading: false,
    error: false,
    errorMessage: '',
    search: '',
    _searchDebounce: null,
    filterType: '',
    showFilterTabs: true,
    thumbSize: 120,
    multiSelect: false,
    selected: [],
    pagination: {
        current_page: 1,
        last_page: 1,
        total_items: 0,
        per_page: 24,
    },
    dragging: false,
    uploading: false,
    uploadProgress: 0,
    uploadFileName: '',
    uploadError: '',
    _uploadFile: null,
    inputAccept: '',
    _onSelect: null,
    _onSelectMulti: null,

    show(options = {}) {
        this.open        = true;
        this.multiSelect = Boolean(options.multi);
        this.showFilterTabs = options.showFilterTabs !== false;
        this.filterType  = String(options.filterType || '');
        this.inputAccept = String(options.accept || '');
        this._onSelect      = typeof options.onSelect === 'function' ? options.onSelect : null;
        this._onSelectMulti = typeof options.onSelectMulti === 'function' ? options.onSelectMulti : null;
        this.activeTab   = 'library';
        this.search      = '';
        this.selected    = [];
        this.uploadFileName = '';
        this.uploadError    = '';
        this.uploading      = false;
        this.uploadProgress = 0;
        this._uploadFile    = null;
        this.files = [];
        this.loadFiles(1);

        requestAnimationFrame(() => {
            const panel = document.getElementById('file-picker-panel');
            if (panel instanceof HTMLElement) {
                panel.focus();
            }
        });
    },

    close() {
        this.open = false;
        this._onSelect = null;
        this._onSelectMulti = null;
    },

    switchTab(tab) {
        this.activeTab = tab;
        if (tab === 'library' && this.files.length === 0) {
            this.loadFiles(1);
        }
    },

    setSearch(value) {
        this.search = String(value || '');
        clearTimeout(this._searchDebounce);
        this._searchDebounce = setTimeout(() => {
            this.loadFiles(1);
        }, 350);
    },

    setFilterType(type) {
        this.filterType = String(type || '');
        this.loadFiles(1);
    },

    changePage(page) {
        const bounded = Math.max(1, Math.min(this.pagination.last_page || 1, page));
        if (bounded !== this.pagination.current_page) {
            this.loadFiles(bounded);
        }
    },

    _panel() {
        return document.getElementById('file-picker-panel');
    },

    async loadFiles(page = 1) {
        this.loading = true;
        this.error = false;
        this.errorMessage = '';

        const panel = this._panel();
        const dataUrl = String(panel?.dataset?.dataUrl || '/files/picker-data');
        const params = new URLSearchParams({
            page: String(page),
            per_page: String(this.pagination.per_page || 24),
        });
        if (this.search.trim() !== '') {
            params.set('search', this.search.trim());
        }
        if (this.filterType !== '') {
            params.set('category', this.filterType);
        }

        try {
            const resp = await fetch(`${dataUrl}?${params.toString()}`, {
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!resp.ok) {
                throw new Error(`HTTP ${resp.status}`);
            }

            const payload = await resp.json();
            // payload.data = API body { status, data: { data: [], meta: {} } }
            const apiWrapper = isObject(payload?.data?.data) ? payload.data.data
                : isObject(payload?.data) ? payload.data
                : {};

            const files = Array.isArray(apiWrapper?.data) ? apiWrapper.data : [];
            const meta = isObject(apiWrapper?.meta) ? apiWrapper.meta : {};

            this.files = files;
            this.pagination = {
                current_page: Number(meta.current_page ?? page),
                last_page: Math.max(1, Number(meta.last_page ?? 1)),
                total_items: Number(meta.total_items ?? meta.total ?? files.length),
                per_page: Number(meta.per_page ?? meta.limit ?? 24),
            };
        } catch (err) {
            devError('[filePicker] loadFiles error:', err);
            this.error = true;
            this.errorMessage = (uiLabels[localePrefix()] || uiLabels.es).loadRetry;
            this.files = [];
        } finally {
            this.loading = false;
        }
    },

    isSelected(file) {
        return this.selected.some((f) => String(f.id) === String(file.id));
    },

    toggleSelected(file) {
        if (this.isSelected(file)) {
            this.selected = this.selected.filter((f) => String(f.id) !== String(file.id));
        } else {
            this.selected.push(file);
        }
    },

    select(file) {
        if (this.multiSelect) {
            this.toggleSelected(file);
        } else {
            if (typeof this._onSelect === 'function') {
                this._onSelect(file);
            }
            this.close();
        }
    },

    confirm() {
        if (typeof this._onSelectMulti === 'function') {
            this._onSelectMulti([...this.selected]);
        }
        this.close();
    },

    onUploadFileChange(event) {
        const file = event?.target?.files?.[0] ?? null;
        if (file) {
            this._uploadFile   = file;
            this.uploadFileName = file.name;
            this.uploadError    = '';
        } else {
            this._uploadFile    = null;
            this.uploadFileName = '';
        }
    },

    async submitUpload() {
        if (!this._uploadFile || this.uploading) {
            return;
        }

        this.uploading      = true;
        this.uploadProgress = 0;
        this.uploadError    = '';

        const panel      = this._panel();
        const uploadUrl  = String(panel?.dataset?.uploadUrl || '/files/upload');
        const csrfName   = String(panel?.dataset?.csrfName || '');
        const csrfHash   = String(panel?.dataset?.csrfHash || '');

        const formData = new FormData();
        formData.append('file', this._uploadFile);
        if (csrfName !== '' && csrfHash !== '') {
            formData.append(csrfName, csrfHash);
        }

        try {
            await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        this.uploadProgress = Math.round((e.loaded / e.total) * 90);
                    }
                });
                xhr.open('POST', uploadUrl);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onload = () => {
                    let json = null;
                    try {
                        json = JSON.parse(xhr.responseText);
                    } catch { /* ignore */ }

                    if (json?.csrf_name && json?.csrf_hash && panel) {
                        panel.dataset.csrfName = String(json.csrf_name);
                        panel.dataset.csrfHash = String(json.csrf_hash);
                    }

                    if (xhr.status >= 200 && xhr.status < 300 && json?.ok !== false) {
                        resolve(json);
                    } else {
                        const msg = json?.messages?.[0] || json?.message || `HTTP ${xhr.status}`;
                        reject(new Error(String(msg)));
                    }
                };
                xhr.onerror = () => reject(new Error('Network error'));
                xhr.send(formData);
            });

            this.uploadProgress = 100;
            this._uploadFile    = null;
            this.uploadFileName = '';
            this.switchTab('library');
            this.loadFiles(1);
        } catch (err) {
            devError('[filePicker] submitUpload error:', err);
            this.uploadError = err instanceof Error ? err.message : 'Upload failed.';
        } finally {
            this.uploading      = false;
            this.uploadProgress = 0;
        }
    },
};
