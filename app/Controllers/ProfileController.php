<?php

namespace App\Controllers;

use App\Services\AuthApiService;
use App\Services\UserApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ProfileController extends BaseWebController
{
    protected AuthApiService $authService;
    protected UserApiService $userService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->authService = service('authApiService');
        $this->userService = service('userApiService');
    }

    public function index(): string
    {
        $this->refreshUserSession();
        $user = session('user') ?? [];
        $isAdmin = ($user['role'] ?? null) === 'admin';

        return $this->render('profile/index', [
            'title'   => lang('Profile.title'),
            'user'    => $user,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function update(): RedirectResponse
    {
        $sessionUser = session('user') ?? [];
        $isAdmin = ($sessionUser['role'] ?? null) === 'admin';

        if (! $isAdmin) {
            return redirect()->to(site_url('profile'))->with('error', lang('Profile.updateNotAllowed'));
        }

        if (! $this->validate([
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
        ])) {
            return redirect()->back()->withInput()->with('fieldErrors', $this->validator->getErrors());
        }

        $payload = [
            'first_name' => (string) $this->request->getPost('first_name'),
            'last_name'  => (string) $this->request->getPost('last_name'),
        ];

        $userId = $sessionUser['id'] ?? null;
        if (! is_scalar($userId) || (string) $userId === '') {
            return redirect()->to(site_url('profile'))->with('error', lang('Profile.updateFailed'));
        }

        $response = $this->safeApiCall(fn() => $this->userService->update((string) $userId, $payload));

        if (! $response['ok']) {
            $fieldErrors = $this->getFieldErrors($response);

            if (! empty($fieldErrors)) {
                return $this->withFieldErrors($fieldErrors);
            }

            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, lang('Profile.updateFailed')));
        }

        $this->refreshUserSession();

        return redirect()->to(site_url('profile'))->with('success', lang('Profile.updateSuccess'));
    }

    public function requestPasswordReset(): RedirectResponse
    {
        $email = trim((string) (session('user.email') ?? ''));
        if ($email === '') {
            return redirect()->to(site_url('profile'))->with('error', lang('Profile.passwordResetFailed'));
        }

        $response = $this->safeApiCall(fn() => $this->authService->forgotPassword(
            $email,
            $this->clientBaseUrl(),
        ));

        if (! $response['ok']) {
            return redirect()->to(site_url('profile'))->with('error', $this->firstMessage($response, lang('Profile.passwordResetFailed')));
        }

        return redirect()->to(site_url('profile'))->with('success', lang('Profile.passwordResetSent'));
    }

    public function resendVerification(): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->authService->resendVerification([
            'client_base_url' => $this->clientBaseUrl(),
        ]));

        if (! $response['ok']) {
            return redirect()->to(site_url('profile'))->with('error', $this->firstMessage($response, lang('Profile.resendFailed')));
        }

        return redirect()->to(site_url('profile'))->with('success', lang('Profile.resendSuccess'));
    }

    protected function refreshUserSession(): void
    {
        $me = $this->safeApiCall(fn() => $this->authService->me());

        if (! $me['ok']) {
            return;
        }

        $user = $this->extractData($me);

        if (! empty($user)) {
            session()->set('user', $user);
        }
    }
}
