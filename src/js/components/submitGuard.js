/**
 * Global submit guard — disables the triggering form's submit controls and
 * shows a blocking overlay for the duration of any mutating (non-GET) form
 * submission, so a double-click or an impatient second click can't fire a
 * duplicate POST/PUT/PATCH/DELETE.
 *
 * A single document-level listener covers every present and future form
 * with zero per-view wiring — no `x-data="submittingOverlay()"` boilerplate
 * needed on each form.
 *
 * Opt out on a specific form (e.g. one that legitimately re-submits itself,
 * or handles its own pending-state UI) with `data-no-submit-guard`.
 */

const OVERLAY_ID = 'global-submit-guard-overlay';
const SAFETY_TIMEOUT_MS = 15000;

function isMutatingForm(form) {
    if (!(form instanceof HTMLFormElement)) {
        return false;
    }

    if (form.hasAttribute('data-no-submit-guard')) {
        return false;
    }

    const method = (form.getAttribute('method') || 'get').toLowerCase();

    return method !== 'get';
}

function showOverlay() {
    if (document.getElementById(OVERLAY_ID)) {
        return;
    }

    // Reuses confirm_modal's backdrop class (bg-black/30) and the standard
    // disabled-control cursor so no new Tailwind utility needs compiling.
    const overlay = document.createElement('div');
    overlay.id = OVERLAY_ID;
    overlay.className = 'fixed inset-0 z-40 cursor-not-allowed bg-black/30';
    overlay.setAttribute('aria-hidden', 'true');
    document.body.appendChild(overlay);
}

function hideOverlay() {
    document.getElementById(OVERLAY_ID)?.remove();
}

function disableSubmitControls(form) {
    const controls = form.querySelectorAll(
        'button[type="submit"], input[type="submit"], button:not([type])'
    );

    controls.forEach((control) => {
        if (control instanceof HTMLButtonElement || control instanceof HTMLInputElement) {
            control.disabled = true;
            control.dataset.submitGuardDisabled = '1';
        }
    });
}

function releaseGuardedControls() {
    document.querySelectorAll('[data-submit-guard-disabled="1"]').forEach((control) => {
        if (control instanceof HTMLButtonElement || control instanceof HTMLInputElement) {
            control.disabled = false;
        }
        control.removeAttribute('data-submit-guard-disabled');
    });
}

/**
 * Attaches the guard once per page load. Idempotent — calling it more than
 * once (e.g. from a stray double `DOMContentLoaded`) does not double-bind.
 */
export function bootGlobalSubmitGuard() {
    if (document.documentElement.dataset.submitGuardBooted === '1') {
        return;
    }
    document.documentElement.dataset.submitGuardBooted = '1';

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!isMutatingForm(form) || event.defaultPrevented) {
            return;
        }

        disableSubmitControls(form);
        showOverlay();

        // Safety net: if navigation never happens (e.g. client-side
        // validation further up the chain silently re-enables the form, or
        // the response loads into a background tab), don't leave the page
        // stuck behind the overlay forever.
        setTimeout(() => {
            hideOverlay();
            releaseGuardedControls();
        }, SAFETY_TIMEOUT_MS);
    });

    // Browsers can restore a disabled-submit-button snapshot from the
    // back/forward cache — clear the guard's leftover state so hitting
    // "back" after a submit never leaves a page stuck with dead buttons.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hideOverlay();
            releaseGuardedControls();
        }
    });
}
