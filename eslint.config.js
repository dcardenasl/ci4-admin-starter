const js = require('@eslint/js');

module.exports = [
    js.configs.recommended,
    {
        files: ['public/assets/js/app.js'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'script',
            globals: {
                Alpine: 'readonly',
                CustomEvent: 'readonly',
                Date: 'readonly',
                FormData: 'readonly',
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
];
