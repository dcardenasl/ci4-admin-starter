/* global alert, prompt */
export const adminMetadataField = (config = {}) => ({
    rows: Array.isArray(config.rows) && config.rows.length > 0
        ? config.rows
        : [{ key: '', value: '' }],
    json: '{}',
    duplicates: [],
    messages: {
        pastePrompt: config.jsonPastePrompt || 'Paste JSON object here:',
        invalidFormat: config.jsonInvalidFormat || 'Invalid JSON: Must be an object.',
        syntaxError: config.jsonSyntaxError || 'Invalid JSON syntax: {error}',
    },

    init() {
        this.sync();
    },

    addRow() {
        this.rows.push({ key: '', value: '' });
        this.sync();
    },

    removeRow(index) {
        this.rows.splice(index, 1);
        if (this.rows.length === 0) {
            this.addRow();
            return;
        }
        this.sync();
    },

    importJson() {
        const raw = prompt(this.messages.pastePrompt);
        if (!raw) return;

        try {
            const parsed = JSON.parse(raw);
            if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
                alert(this.messages.invalidFormat);
                return;
            }

            const newRows = Object.entries(parsed).map(([key, value]) => ({
                key,
                value: typeof value === 'object' ? JSON.stringify(value) : String(value)
            }));

            if (newRows.length > 0) {
                this.rows = newRows;
                this.sync();
            }
        } catch (e) {
            alert(this.messages.syntaxError.replace('{error}', e.message));
        }
    },

    sync() {
        const metadata = {};
        const keys = [];
        this.duplicates = [];

        this.rows.forEach((row, index) => {
            const key = String(row.key || '').trim();
            if (key === '') return;

            if (keys.includes(key)) {
                this.duplicates.push(index);
            }
            keys.push(key);

            let val = String(row.value || '').trim();
            if (val === 'true') val = true;
            else if (val === 'false') val = false;
            else if (val === 'null') val = null;
            else if (!isNaN(val) && val !== '') val = Number(val);

            metadata[key] = val;
        });

        this.json = JSON.stringify(metadata, null, 2);
    },
});
