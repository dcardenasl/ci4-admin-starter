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
                FormData: 'readonly',
                HTMLFormElement: 'readonly',
                HTMLElement: 'readonly',
                HTMLInputElement: 'readonly',
                Intl: 'readonly',
                URL: 'readonly',
                URLSearchParams: 'readonly',
                XMLHttpRequest: 'readonly',
                console: 'readonly',
                clearInterval: 'readonly',
                clearTimeout: 'readonly',
                document: 'readonly',
                fetch: 'readonly',
                navigator: 'readonly',
                requestAnimationFrame: 'readonly',
                setInterval: 'readonly',
                setTimeout: 'readonly',
                window: 'readonly',
            },
        },
        rules: {
            'no-shadow': 'error',
        },
    },
];
