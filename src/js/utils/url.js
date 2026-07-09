/**
 * Converts a URL query string to a plain object, discarding empty values.
 *
 * @param {string} search - A query string (e.g. `window.location.search`)
 * @returns {Record<string, string>} Key/value pairs with blank entries removed
 */
export const queryToObject = (search) => {
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

/**
 * Converts a plain object to a URL query string, skipping blank string values.
 *
 * @param {Record<string, unknown>} query - Object of query parameters
 * @returns {string} URL-encoded query string (without leading `?`)
 */
export const objectToQueryString = (query) => {
    const params = new URLSearchParams();

    Object.entries(query || {}).forEach(([key, value]) => {
        if (typeof value === 'string' && value.trim() !== '') {
            params.append(key, value.trim());
        }
    });

    return params.toString();
};

/**
 * Extracts all non-empty named string fields from an HTMLFormElement.
 *
 * @param {HTMLFormElement} form - The form to read values from
 * @returns {Record<string, string>} Key/value pairs for non-blank fields
 */
export const formToQuery = (form) => {
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

/**
 * Returns true if value is a non-null, non-array plain object.
 *
 * @param {unknown} value - Value to test
 * @returns {boolean}
 */
export const isObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);

/**
 * Normalises an API list response to the object that contains `{items, pagination}`.
 * Handles both top-level arrays and nested `{ data: { data: [], meta: {} } }` wrappers.
 *
 * @param {unknown} payload - Raw API response body
 * @returns {Record<string, unknown>} Normalised root object
 */
export const tablePayloadRoot = (payload) => {
    if (Array.isArray(payload)) {
        return { data: payload };
    }

    if (!isObject(payload)) {
        return {};
    }

    const nested = payload.data;
    if (!isObject(nested)) {
        // If it's a simple object but payload itself is the data (e.g. single item)
        return payload;
    }

    // Heuristic: If it looks like a pagination wrapper or a result with meta
    if (Array.isArray(nested.data) || isObject(nested.meta) ||
        nested.current_page !== undefined || nested.page !== undefined ||
        nested.last_page !== undefined ||
        nested.total_items !== undefined || isObject(nested.summary)) {
        return nested;
    }

    return payload;
};
