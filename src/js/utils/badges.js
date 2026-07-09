/**
 * Returns Tailwind CSS classes for a user/resource status badge.
 *
 * @param {string} status - Status value (e.g. 'active', 'pending', 'suspended')
 * @returns {string} Tailwind CSS class string
 */
export const statusBadgeClass = (status) => {
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

/**
 * Returns Tailwind CSS classes for an audit action badge.
 *
 * @param {string} action - Audit action value (e.g. 'create', 'update', 'delete', 'login')
 * @returns {string} Tailwind CSS class string
 */
export const auditActionBadgeClass = (action) => {
    const val = String(action || '').toLowerCase();

    if (val === 'create') return 'bg-green-100 text-green-800';
    if (val === 'update') return 'bg-blue-100 text-blue-800';
    if (val === 'delete') return 'bg-red-100 text-red-800';
    if (['login', 'login_success'].includes(val)) return 'bg-brand-100 text-brand-800';
    if (val === 'login_failure') return 'bg-red-100 text-red-800';
    if (val === 'logout') return 'bg-gray-100 text-gray-800';
    if (val === 'approve') return 'bg-emerald-100 text-emerald-800';

    return 'bg-gray-100 text-gray-700';
};

/**
 * Returns Tailwind CSS classes for an audit result badge.
 *
 * @param {string} result - Audit result value (e.g. 'success', 'failure', 'denied')
 * @returns {string} Tailwind CSS class string
 */
export const auditResultBadgeClass = (result) => {
    const val = String(result || '').toLowerCase();

    if (val === 'success') return 'bg-green-100 text-green-800';
    if (val === 'failure') return 'bg-red-100 text-red-800';
    if (val === 'denied') return 'bg-orange-100 text-orange-800';

    return 'bg-gray-100 text-gray-700';
};

/**
 * Returns Tailwind CSS classes for an audit severity badge.
 *
 * @param {string} severity - Severity level (e.g. 'info', 'warning', 'critical')
 * @returns {string} Tailwind CSS class string
 */
export const auditSeverityBadgeClass = (severity) => {
    const val = String(severity || '').toLowerCase();

    if (val === 'info') return 'bg-blue-50 text-blue-700 border border-blue-200';
    if (val === 'warning') return 'bg-amber-50 text-amber-700 border border-amber-200';
    if (val === 'critical') return 'bg-red-100 text-red-700 border border-red-300 font-bold';

    return 'bg-gray-100 text-gray-600 border border-gray-200';
};
