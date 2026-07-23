/**
 * CI4 Admin Starter — Application JavaScript entry point.
 *
 * Bundled via esbuild (see package.json `dev:js` / `build:js`) into
 * `public/assets/js/app.js` as a build artifact — do not edit that file
 * directly, it is generated.
 *
 * Only symbols required by Alpine.js (component factories, stores) and
 * the Google Identity callback are exposed on `window`.
 *
 * Alpine components are registered via Alpine.data() / Alpine.store() inside
 * the 'alpine:init' event listener and do NOT need to be on window, except
 * where a view references them as a bare identifier outside of x-data
 * (e.g. `remoteTable({...})` composed via `Object.assign()` in inline
 * x-data expressions — see files/index.php and files/trash.php).
 */
import { bootLucideIcons } from './utils/lucide.js';
import { bootSlugFields } from './utils/slug.js';

import { bootGlobalSubmitGuard } from './components/submitGuard.js';

import { confirmStore } from './stores/confirm.store.js';
import { toastStore } from './stores/toast.store.js';
import { filePickerStore } from './stores/filePicker.store.js';

import { appShell } from './components/appShell.js';
import { remoteTableFactory } from './components/remoteTable.js';
import { filePickerField } from './components/filePickerField.js';
import { adminMetadataField } from './components/adminMetadataField.js';
import { adminMediaGallery } from './components/adminMediaGallery.js';
import { bootSessionExpiryWatcher } from './components/sessionWatcher.js';
import { handleGoogleCredentialResponse } from './components/googleAuth.js';

document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', confirmStore());
    Alpine.store('toast', toastStore);
    Alpine.store('filePicker', filePickerStore);

    Alpine.data('appShell', appShell);
    Alpine.data('remoteTable', remoteTableFactory);
    Alpine.data('filePickerField', filePickerField);
    Alpine.data('adminMetadataField', adminMetadataField);
    Alpine.data('adminMediaGallery', adminMediaGallery);

    // Backward compatibility mappings for historic templates
    Alpine.data('catalogMetadataField', (config = {}) => Alpine.data('adminMetadataField')(config));
    Alpine.data('catalogItemMedia', (config = {}) => Alpine.data('adminMediaGallery')(config));

    // window.remoteTable is used directly (not via x-data) by inline
    // `Object.assign({...}, remoteTable({...}))` expressions in
    // files/index.php and files/partials/list_section.php.
    window.remoteTable = remoteTableFactory;
});

document.addEventListener('DOMContentLoaded', () => {
    bootLucideIcons();
    bootSlugFields();
    bootGlobalSubmitGuard();
    const config = window.__componentConfig || {};
    bootSessionExpiryWatcher({ expiringMessage: config.sessionExpiringMessage });
});

window.addEventListener('load', () => {
    bootLucideIcons();
});

/**
 * Google Identity Services callback.
 * Must be on `window` because it is referenced by the Google GSI script
 * via the data-callback attribute on the sign-in button (auth/login.php).
 */
window.handleGoogleCredentialResponse = handleGoogleCredentialResponse;
