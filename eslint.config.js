const js = require('@eslint/js');

module.exports = [
    js.configs.recommended,
    {
        files: ['src/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                Alpine: 'readonly',
                CustomEvent: 'readonly',
                Date: 'readonly',
                FormData: 'readonly',
                HTMLButtonElement: 'readonly',
                HTMLFormElement: 'readonly',
                HTMLElement: 'readonly',
                HTMLInputElement: 'readonly',
                HTMLMetaElement: 'readonly',
                Intl: 'readonly',
                Math: 'readonly',
                Number: 'readonly',
                URL: 'readonly',
                URLSearchParams: 'readonly',
                XMLHttpRequest: 'readonly',
                console: 'readonly',
                clearInterval: 'readonly',
                clearTimeout: 'readonly',
                document: 'readonly',
                fetch: 'readonly',
                navigator: 'readonly',
                parseInt: 'readonly',
                requestAnimationFrame: 'readonly',
                setInterval: 'readonly',
                setTimeout: 'readonly',
                sessionStorage: 'readonly',
                window: 'readonly',
            },
        },
        rules: {
            'no-shadow': 'error',
        },
    },
    {
        // Ignore generated output — linting the source is sufficient
        ignores: ['public/assets/js/app.js'],
    },
];
