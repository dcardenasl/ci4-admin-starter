export const localePrefix = () => String(document.documentElement?.lang || 'es').toLowerCase().startsWith('en') ? 'en' : 'es';
export const localeTag = () => (localePrefix() === 'en' ? 'en-US' : 'es-ES');
export const focusableSelector = 'a[href], button:not([disabled]), textarea, input:not([type="hidden"]):not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

export const uiLabels = {
    es: {
        confirmAction: 'Confirmar acción',
        confirm: 'Confirmar',
        requestFailed: 'La solicitud falló (HTTP {status}).',
        loadRetry: 'No se pudo cargar la información. Intenta nuevamente.'
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

const auditActionLabels = {
    es: {
        create: 'Crear',
        update: 'Actualizar',
        delete: 'Eliminar',
        login: 'Iniciar sesion',
        login_success: 'Inicio de sesion exitoso',
        login_failure: 'Inicio de sesion fallido',
        logout: 'Cerrar sesion',
        approve: 'Aprobar'
    },
    en: {
        create: 'Create',
        update: 'Update',
        delete: 'Delete',
        login: 'Login',
        login_success: 'Login Success',
        login_failure: 'Login Failure',
        logout: 'Logout',
        approve: 'Approve'
    }
};

const auditResultLabels = {
    es: {
        success: 'Exito',
        failure: 'Fallo',
        denied: 'Denegado'
    },
    en: {
        success: 'Success',
        failure: 'Failure',
        denied: 'Denied'
    }
};

const auditSeverityLabels = {
    es: {
        info: 'Info',
        warning: 'Advertencia',
        critical: 'Critico'
    },
    en: {
        info: 'Info',
        warning: 'Warning',
        critical: 'Critical'
    }
};

export const paginationLabels = {
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

/**
 * Returns the localised display label for a status value.
 *
 * @param {string} status - Status value (e.g. 'active', 'pending')
 * @returns {string} Human-readable label in the current page locale
 */
export const statusLabel = (status) => {
    const value = String(status || '').trim();
    if (value === '') {
        return '-';
    }

    const key = value.toLowerCase();
    const locale = localePrefix();

    return statusLabels[locale]?.[key] || value;
};

/**
 * Returns the localised display label for an audit action value.
 *
 * @param {string} action - Audit action value (e.g. 'create', 'login')
 * @returns {string} Human-readable label in the current page locale
 */
export const auditActionLabel = (action) => {
    const value = String(action || '').trim();
    if (value === '') {
        return '-';
    }

    const key = value.toLowerCase();
    const locale = localePrefix();

    return auditActionLabels[locale]?.[key] || value;
};

/**
 * Returns the localised display label for an audit result value.
 *
 * @param {string} result - Audit result value (e.g. 'success', 'failure', 'denied')
 * @returns {string} Human-readable label in the current page locale
 */
export const auditResultLabel = (result) => {
    const value = String(result || '').trim();
    if (value === '') {
        return '-';
    }

    const key = value.toLowerCase();
    const locale = localePrefix();

    return auditResultLabels[locale]?.[key] || value;
};

/**
 * Returns the localised display label for an audit severity level.
 *
 * @param {string} severity - Severity level (e.g. 'info', 'warning', 'critical')
 * @returns {string} Human-readable label in the current page locale
 */
export const auditSeverityLabel = (severity) => {
    const value = String(severity || '').trim();
    if (value === '') {
        return '-';
    }

    const key = value.toLowerCase();
    const locale = localePrefix();

    return auditSeverityLabels[locale]?.[key] || value;
};
