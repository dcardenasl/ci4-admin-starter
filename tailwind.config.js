/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './app/Views/**/*.php',
        './app/Helpers/**/*.php',
        './public/assets/js/**/*.js'
    ],
    safelist: [
        // Table row striping
        'odd:bg-white',
        'even:bg-gray-50/45',
        'hover:bg-brand-50/40',
        // Table header gradient
        'bg-gradient-to-b',
        'from-gray-50',
        'to-gray-100',
        // Filter panel gradient
        'bg-gradient-to-br',
        'to-white',
        // Custom padding
        'py-3.5',
        // Custom text sizes
        'text-[11px]',
        // Brand color variants with opacity
        'hover:bg-brand-50/40',
        'bg-brand-50/40',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50: 'rgb(var(--color-brand-50) / <alpha-value>)',
                    100: 'rgb(var(--color-brand-100) / <alpha-value>)',
                    200: 'rgb(var(--color-brand-200) / <alpha-value>)',
                    300: 'rgb(var(--color-brand-300) / <alpha-value>)',
                    400: 'rgb(var(--color-brand-400) / <alpha-value>)',
                    500: 'rgb(var(--color-brand-500) / <alpha-value>)',
                    600: 'rgb(var(--color-brand-600) / <alpha-value>)',
                    700: 'rgb(var(--color-brand-700) / <alpha-value>)',
                    800: 'rgb(var(--color-brand-800) / <alpha-value>)',
                    900: 'rgb(var(--color-brand-900) / <alpha-value>)'
                }
            },
            fontFamily: {
                sans: ['var(--font-sans)'],
                mono: ['var(--font-mono)']
            }
        }
    },
    plugins: []
};
