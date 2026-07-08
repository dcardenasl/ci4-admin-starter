/**
 * Hydrates Lucide icon placeholders in the DOM.
 *
 * @returns {boolean} True if Lucide was available and icons were rendered, false otherwise
 */
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

/**
 * Retries icon hydration until the Lucide CDN script is ready.
 * Polls up to 20 times at 150 ms intervals, then gives up gracefully.
 *
 * @returns {void}
 */
export const bootLucideIcons = () => {
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
