<style>
    :root {
        --color-brand-50: 239 246 255;
        --color-brand-600: 37 99 235;
        --color-brand-700: 29 78 216;
        --font-sans: "Inter", system-ui, -apple-system, sans-serif;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        min-height: 100vh;
        background-color: #f9fafb;
        color: #111827;
        font-family: var(--font-sans);
        line-height: 1.5;
    }

    .error-wrap {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 1.5rem;
    }

    .error-card {
        width: 100%;
        max-width: 40rem;
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        box-shadow: 0 1px 2px rgb(0 0 0 / 0.06);
        padding: 2rem;
    }

    .error-code {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgb(var(--color-brand-600));
    }

    .error-title {
        margin: 0.375rem 0 0;
        font-size: 1.875rem;
        line-height: 1.2;
        color: #111827;
    }

    .error-copy {
        margin: 0.875rem 0 0;
        color: #4b5563;
    }

    .error-debug {
        margin-top: 1.25rem;
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background-color: #f3f4f6;
        color: #374151;
        font-size: 0.875rem;
        word-break: break-word;
    }

    .error-actions {
        margin-top: 1.25rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.625rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        border: 1px solid transparent;
        padding: 0.5rem 0.875rem;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-primary {
        background-color: rgb(var(--color-brand-600));
        color: #ffffff;
    }

    .btn-primary:hover,
    .btn-primary:focus-visible {
        background-color: rgb(var(--color-brand-700));
    }

    .btn-secondary {
        background-color: #ffffff;
        border-color: #d1d5db;
        color: #374151;
    }

    .btn-secondary:hover,
    .btn-secondary:focus-visible {
        background-color: #f9fafb;
    }
</style>
