/**
 * Dev-only logging.
 * Reads the data-env attribute injected by head.php (<?= ENVIRONMENT ?>).
 * Errors are suppressed in production to avoid leaking internal details.
 */
export const isDev = String(document.documentElement.dataset.env || '').toLowerCase() === 'development';

/** @param {...unknown} args */
export const devError = (...args) => { if (isDev) console.error(...args); };
