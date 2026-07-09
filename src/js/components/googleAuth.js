import { devError } from '../utils/dev.js';

/**
 * Google Identity Services callback.
 * Must be on `window` because it is referenced by the Google GSI script
 * via the data-callback attribute on the sign-in button.
 *
 * @param {{ credential: string }} response - The Google credential response object
 */
export const handleGoogleCredentialResponse = (response) => {
    const token = response && typeof response.credential === 'string' ? response.credential : '';
    if (token === '') {
        devError('[Google Auth] Empty credential in response');
        return;
    }

    const tokenInput = document.getElementById('google-id-token');
    const loginForm = document.getElementById('google-login-form');
    if (!(tokenInput instanceof HTMLInputElement) || !(loginForm instanceof HTMLFormElement)) {
        devError('[Google Auth] Required form elements not found in DOM');
        return;
    }

    window.dispatchEvent(new CustomEvent('login:loading', { detail: { flow: 'google' } }));

    tokenInput.value = token;
    loginForm.submit();
};
