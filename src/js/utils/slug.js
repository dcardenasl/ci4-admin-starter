/* global AbortController */
import { devError } from './dev.js';

export const slugify = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-')
    .slice(0, 255);

export const bootSlugFields = () => {
    document.querySelectorAll('input[data-slug-source]').forEach((slugInput) => {
        if (!(slugInput instanceof HTMLInputElement)) {
            return;
        }

        const sourceSelector = slugInput.dataset.slugSource || '';
        const sourceInput = sourceSelector === '' ? null : document.querySelector(sourceSelector);
        const regenerateButton = slugInput
            .closest('[data-slug-field]')
            ?.querySelector('[data-slug-regenerate]');
        const checkUrl = slugInput.dataset.slugCheckUrl || '';
        const currentId = slugInput.dataset.slugCurrentId || '';
        const statusIcons = slugInput
            .closest('[data-slug-field]')
            ?.querySelectorAll('[data-slug-status]') || [];

        if (!(sourceInput instanceof HTMLInputElement)) {
            return;
        }

        let manual = slugInput.value.trim() !== '' && slugInput.value.trim() !== slugify(sourceInput.value);
        let availabilityTimer = 0;
        let availabilityRequest = null;

        const showStatus = (status) => {
            statusIcons.forEach((icon) => {
                if (!(icon instanceof HTMLElement)) {
                    return;
                }

                const active = icon.dataset.slugStatus === status;
                icon.classList.toggle('hidden', !active);
                icon.classList.toggle('flex', active);
            });
        };

        const checkAvailability = () => {
            window.clearTimeout(availabilityTimer);

            if (availabilityRequest !== null) {
                availabilityRequest.abort();
                availabilityRequest = null;
            }

            const slug = slugInput.value.trim();
            if (checkUrl === '' || slug.length < 2 || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) {
                showStatus('');
                slugInput.setCustomValidity('');
                return;
            }

            availabilityTimer = window.setTimeout(() => {
                const url = new URL(checkUrl, window.location.origin);
                url.searchParams.set('slug', slug);
                if (currentId !== '') {
                    url.searchParams.set('current_id', currentId);
                }

                const controller = new AbortController();
                availabilityRequest = controller;
                showStatus('checking');

                fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal
                })
                    .then((response) => response.ok ? response.json() : Promise.reject(new Error(String(response.status))))
                    .then((payload) => {
                        const available = payload && payload.available === true;
                        showStatus(available ? 'available' : 'unavailable');

                        if (available) {
                            slugInput.setCustomValidity('');
                            return;
                        }

                        const unavailableIcon = Array.from(statusIcons).find((icon) => (
                            icon instanceof HTMLElement && icon.dataset.slugStatus === 'unavailable'
                        ));
                        slugInput.setCustomValidity(unavailableIcon instanceof HTMLElement ? unavailableIcon.title : 'Slug unavailable');
                    })
                    .catch((error) => {
                        if (error && error.name === 'AbortError') {
                            return;
                        }

                        showStatus('');
                        slugInput.setCustomValidity('');
                        devError('Slug availability check failed', error);
                    })
                    .finally(() => {
                        if (availabilityRequest === controller) {
                            availabilityRequest = null;
                        }
                    });
            }, 350);
        };

        const syncFromSource = () => {
            if (manual) {
                return;
            }

            slugInput.value = slugify(sourceInput.value);
            checkAvailability();
        };

        sourceInput.addEventListener('input', syncFromSource);
        slugInput.addEventListener('input', () => {
            const normalized = slugify(slugInput.value);
            manual = normalized !== '' && normalized !== slugify(sourceInput.value);
            slugInput.value = normalized;
            checkAvailability();
        });

        if (regenerateButton instanceof HTMLButtonElement) {
            regenerateButton.addEventListener('click', () => {
                manual = false;
                syncFromSource();
                slugInput.focus();
            });
        }

        syncFromSource();
        checkAvailability();
    });
};
