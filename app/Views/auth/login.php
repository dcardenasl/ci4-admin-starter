<?php
$googleEnabled = (bool) ($googleEnabled ?? false);
$googleClientId = (string) ($googleClientId ?? '');
?>

<div x-data="{ isLoading: false, loadingFlow: 'none' }"
     @login:loading.window="isLoading = true; loadingFlow = $event.detail?.flow ?? 'google'"
     class="relative">

    <div x-show="isLoading" style="display:none"
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/95">
        <div class="flex flex-col items-center gap-3">
            <svg class="h-8 w-8 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-700" x-text="loadingFlow === 'google' ? '<?= lang('Auth.login_signing_in_google') ?>' : '<?= lang('Auth.login_signing_in') ?>'"></span>
        </div>
    </div>

    <form @submit="isLoading = true; loadingFlow = 'password'"
        method="post" action="<?= site_url('login') ?>" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-gray-700" for="email"><?= lang('Auth.email_label') ?></label>
            <input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required
                class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 border-gray-300 focus:border-brand-500 focus:ring-brand-500 <?= field_error_class('email') ?>">
            <?= render_field_error('email') ?>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700" for="password"><?= lang('Auth.password_label') ?></label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 border-gray-300 focus:border-brand-500 focus:ring-brand-500 <?= field_error_class('password') ?>">
            <?= render_field_error('password') ?>
        </div>
        <button type="submit" :disabled="isLoading"
            class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 font-medium hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 disabled:opacity-60 disabled:cursor-not-allowed">
            <?= lang('Auth.login_button') ?>
        </button>
    </form>

    <?php if ($googleEnabled): ?>
        <div class="relative my-5">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-white px-3 text-xs font-medium uppercase tracking-wide text-gray-500"><?= lang('Auth.google_or_divider') ?></span>
            </div>
        </div>

        <div id="g_id_onload"
            data-client_id="<?= esc($googleClientId) ?>"
            data-context="signin"
            data-ux_mode="popup"
            data-callback="handleGoogleCredentialResponse"
            data-auto_prompt="false">
        </div>
        <div class="relative">
            <div x-show="isLoading" class="absolute inset-0 z-[9999] cursor-not-allowed" style="display:none"></div>
            <div class="flex justify-center">
                <div class="g_id_signin"
                    data-type="standard"
                    data-shape="pill"
                    data-theme="outline"
                    data-text="signin_with"
                    data-size="large"
                    data-logo_alignment="left"
                    data-width="320">
                </div>
            </div>
        </div>

        <form id="google-login-form" method="post" action="<?= site_url('login/google') ?>" class="hidden">
            <?= csrf_field() ?>
            <input type="hidden" id="google-id-token" name="id_token" value="">
        </form>

        <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php endif; ?>

    <div class="mt-4 text-sm text-gray-600 flex items-center justify-between">
        <a href="<?= site_url('forgot-password') ?>" :class="{ 'pointer-events-none opacity-50': isLoading }" class="text-brand-600 hover:text-brand-700"><?= lang('Auth.forgot_password') ?></a>
        <a href="<?= site_url('register') ?>" :class="{ 'pointer-events-none opacity-50': isLoading }" class="text-brand-600 hover:text-brand-700"><?= lang('Auth.create_account') ?></a>
    </div>

</div>
