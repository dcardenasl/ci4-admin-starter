<?php
$appName = $appName ?? 'API Client';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($title ?? $appName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    [x-cloak] {
        display: none !important;
    }

    :root {
        --color-brand-50: 239 246 255;
        --color-brand-100: 219 234 254;
        --color-brand-200: 191 219 254;
        --color-brand-300: 147 197 253;
        --color-brand-400: 96 165 250;
        --color-brand-500: 59 130 246;
        --color-brand-600: 37 99 235;
        --color-brand-700: 29 78 216;
        --color-brand-800: 30 64 175;
        --color-brand-900: 30 58 138;
        --font-sans: "Inter", system-ui, -apple-system, sans-serif;
        --font-mono: "JetBrains Mono", ui-monospace, monospace;
    }
</style>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        50: "rgb(var(--color-brand-50) / <alpha-value>)",
                        100: "rgb(var(--color-brand-100) / <alpha-value>)",
                        200: "rgb(var(--color-brand-200) / <alpha-value>)",
                        300: "rgb(var(--color-brand-300) / <alpha-value>)",
                        400: "rgb(var(--color-brand-400) / <alpha-value>)",
                        500: "rgb(var(--color-brand-500) / <alpha-value>)",
                        600: "rgb(var(--color-brand-600) / <alpha-value>)",
                        700: "rgb(var(--color-brand-700) / <alpha-value>)",
                        800: "rgb(var(--color-brand-800) / <alpha-value>)",
                        900: "rgb(var(--color-brand-900) / <alpha-value>)"
                    }
                },
                fontFamily: {
                    sans: ["var(--font-sans)"],
                    mono: ["var(--font-mono)"]
                }
            }
        }
    };
</script>
