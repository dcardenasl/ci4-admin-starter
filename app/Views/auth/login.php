<?php
$googleEnabled = (bool) ($googleEnabled ?? false);
$googleClientId = (string) ($googleClientId ?? '');
?>

<form method="post" action="<?= site_url('login') ?>" class="space-y-4">
    <?= csrf_field() ?>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="email"><?= lang('Auth.emailLabel') ?></label>
        <input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required
            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('email') ?>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="password"><?= lang('Auth.passwordLabel') ?></label>
        <input id="password" name="password" type="password" autocomplete="current-password" required
            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('password') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('password') ?>
    </div>
    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 font-medium hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Auth.loginButton') ?></button>
</form>

<?php if ($googleEnabled): ?>
    <div class="relative my-5">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="bg-white px-3 text-xs font-medium uppercase tracking-wide text-gray-500"><?= lang('Auth.googleOrDivider') ?></span>
        </div>
    </div>

    <div id="g_id_onload"
        data-client_id="<?= esc($googleClientId) ?>"
        data-context="signin"
        data-ux_mode="popup"
        data-callback="handleGoogleCredentialResponse"
        data-auto_prompt="false">
    </div>
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

    <form id="google-login-form" method="post" action="<?= site_url('login/google') ?>" class="hidden">
        <?= csrf_field() ?>
        <input type="hidden" id="google-id-token" name="id_token" value="">
    </form>

    <script>
        window.handleGoogleCredentialResponse = function (response) {
            var token = response && response.credential ? response.credential : '';
            if (!token) {
                return;
            }

            var tokenInput = document.getElementById('google-id-token');
            var loginForm = document.getElementById('google-login-form');
            if (!tokenInput || !loginForm) {
                return;
            }

            tokenInput.value = token;
            loginForm.submit();
        };
    </script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>

<div class="mt-4 text-sm text-gray-600 flex items-center justify-between">
    <a href="<?= site_url('forgot-password') ?>" class="text-brand-600 hover:text-brand-700"><?= lang('Auth.forgotPassword') ?></a>
    <a href="<?= site_url('register') ?>" class="text-brand-600 hover:text-brand-700"><?= lang('Auth.createAccount') ?></a>
</div>
