import { localeTag } from './labels.js';

/**
 * Converts various date representations to a scalar suitable for `<input type="date">`.
 * Handles strings, numbers, arrays, and objects with common date-field names.
 *
 * @param {unknown} value - Input value to normalise
 * @returns {string|number|null} ISO date string, numeric timestamp, or null if not convertible
 */
export const toDateInput = (value) => {
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
        if (typeof value.created_at === 'string' || typeof value.created_at === 'number') {
            return value.created_at;
        }
        if (typeof value.value === 'string' || typeof value.value === 'number') {
            return value.value;
        }
    }

    return null;
};

/**
 * Formats a date value to a locale-aware human-readable string (dd/mm/yyyy hh:mm).
 * Returns `'-'` for null/empty input and the raw candidate string if parsing fails.
 *
 * @param {unknown} value - Date value accepted by `toDateInput()`
 * @returns {string} Formatted date string or `'-'`
 */
export const formatDate = (value) => {
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
