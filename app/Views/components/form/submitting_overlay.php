<?php
/**
 * @var string $message
 */
$message = (string) ($message ?? '');
?>

<div x-show="submitting" x-cloak style="display:none" class="fixed inset-0 z-[9999] bg-slate-950/45 backdrop-blur-[2px]"></div>
<div x-show="submitting" x-cloak style="display:none" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
    <div class="inline-flex max-w-[92vw] items-center gap-3 rounded-2xl border border-brand-100 bg-white px-5 py-4 text-sm font-medium text-brand-700 shadow-2xl">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span class="leading-tight"><?= esc($message) ?></span>
    </div>
</div>
