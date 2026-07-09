import { devError } from '../utils/dev.js';
import { isObject } from '../utils/url.js';

const normalizePickerFile = (file) => {
    if (!file) return {};
    return {
        id: file.id ?? '',
        original_name: file.original_name ?? file.name ?? '',
        mime_type: file.mime_type ?? '',
        category: file.category ?? '',
        is_image: file.is_image ?? (file.category === 'image'),
        url: file.url ?? '',
        human_size: file.human_size ?? '',
        variants: file.variants ?? {}
    };
};

export const adminMediaGallery = (config = {}) => ({
    rows: Array.isArray(config.rows) ? config.rows : [],

    init() {
        if (this.rows.length === 0) {
            this.addRow('cover');
        }

        this.rows.forEach((row) => {
            if (!isObject(row.file)) {
                row.file = {};
            }
            if (row.hub_file_id) {
                this.loadFileInfo(row);
            }
        });
    },

    addRow(type = 'gallery') {
        this.rows.push({
            type,
            hub_file_id: '',
            external_url: '',
            alt_text: '',
            caption: '',
            sort_order: this.rows.length,
            is_active: true,
            file: {},
        });
    },

    removeRow(index) {
        this.rows.splice(index, 1);
    },

    chooseFile(row) {
        Alpine.store('filePicker').show({
            filterType: 'image',
            accept: 'image/*',
            multi: false,
            onSelect: (file) => {
                const selected = normalizePickerFile(file);
                row.hub_file_id = String(selected.id ?? '');
                row.external_url = '';
                row.file = {
                    original_name: String(selected.original_name || ''),
                    mime_type: String(selected.mime_type || ''),
                    category: String(selected.category || ''),
                    is_image: Boolean(selected.is_image),
                    url: String(selected.url || ''),
                    human_size: String(selected.human_size || ''),
                    variants: selected.variants || {},
                };
            },
        });
    },

    clearFile(row) {
        row.hub_file_id = '';
        row.file = {};
    },

    fileName(row) {
        return String(row.file?.original_name || (row.hub_file_id ? `#${row.hub_file_id}` : ''));
    },

    async loadFileInfo(row) {
        const panel = document.getElementById('file-picker-panel');
        const baseUrl = panel?.dataset?.dataUrl
            ? String(panel.dataset.dataUrl).replace('/picker-data', '')
            : '/files';

        try {
            const resp = await fetch(`${baseUrl}/${encodeURIComponent(String(row.hub_file_id))}/picker-info`, {
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
            if (payload?.ok && isObject(payload?.data)) {
                const d = normalizePickerFile(payload.data);
                row.file = {
                    original_name: String(d.original_name || ''),
                    mime_type: String(d.mime_type || ''),
                    category: String(d.category || ''),
                    is_image: Boolean(d.is_image),
                    url: String(d.url || ''),
                    human_size: String(d.human_size || ''),
                    variants: d.variants || {},
                };
            }
        } catch (err) {
            devError('[adminMediaGallery] loadFileInfo error:', err);
        }
    },
});
