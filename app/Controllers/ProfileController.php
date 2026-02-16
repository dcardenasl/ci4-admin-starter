<?php

namespace App\Controllers;

use App\Services\AuthApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ProfileController extends BaseWebController
{
    protected AuthApiService $authService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->authService = service('authApiService');
    }

    public function index(): string
    {
        return $this->render('profile/index', [
            'title' => lang('Profile.title'),
            'user'  => session('user') ?? [],
        ]);
    }

    public function update(): RedirectResponse
    {
        if (! $this->validate([
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
        ])) {
            return redirect()->back()->withInput()->with('fieldErrors', $this->validator->getErrors());
        }

        $payload = [
            'first_name' => (string) $this->request->getPost('first_name'),
            'last_name'  => (string) $this->request->getPost('last_name'),
            'avatar_url' => (string) $this->request->getPost('avatar_url'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->updateProfile($payload));

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

    public function changePassword(): RedirectResponse
    {
        if (! $this->validate([
            'current_password'      => 'required',
            'password'              => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ])) {
            return redirect()->back()->with('fieldErrors', $this->validator->getErrors());
        }

        $payload = [
            'current_password'      => (string) $this->request->getPost('current_password'),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->changePassword($payload));

        if (! $response['ok']) {
            $fieldErrors = $this->getFieldErrors($response);

            if (! empty($fieldErrors)) {
                return $this->withFieldErrors($fieldErrors);
            }

            return redirect()->back()->with('error', $this->firstMessage($response, lang('Profile.passwordFailed')));
        }

        return redirect()->to(site_url('profile'))->with('success', lang('Profile.passwordSuccess'));
    }

    public function resendVerification(): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->authService->resendVerification());

        if (! $response['ok']) {
            return redirect()->to(site_url('profile'))->with('error', $this->firstMessage($response, lang('Profile.resendFailed')));
        }

        return redirect()->to(site_url('profile'))->with('success', lang('Profile.resendSuccess'));
    }

    protected function refreshUserSession(): void
    {
        $me = $this->safeApiCall(fn () => $this->authService->me());

        if (! $me['ok']) {
            return;
        }

        $user = $this->extractData($me);

        if (! empty($user)) {
            session()->set('user', $user);
        }
    }
}
