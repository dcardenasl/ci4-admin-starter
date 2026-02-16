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
            'title' => 'Perfil',
            'user'  => session('user') ?? [],
        ]);
    }

    public function update(): RedirectResponse
    {
        if (! $this->validate([
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = [
            'first_name' => (string) $this->request->getPost('first_name'),
            'last_name'  => (string) $this->request->getPost('last_name'),
            'avatar_url' => (string) $this->request->getPost('avatar_url'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->updateProfile($payload));

        if (! $response['ok']) {
            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, 'No se pudo actualizar el perfil.'));
        }

        $this->refreshUserSession();

        return redirect()->to(site_url('profile'))->with('success', 'Perfil actualizado.');
    }

    public function changePassword(): RedirectResponse
    {
        if (! $this->validate([
            'current_password'      => 'required',
            'password'              => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $payload = [
            'current_password'      => (string) $this->request->getPost('current_password'),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->changePassword($payload));

        if (! $response['ok']) {
            return redirect()->back()->with('error', $this->firstMessage($response, 'No se pudo cambiar el password.'));
        }

        return redirect()->to(site_url('profile'))->with('success', 'Password actualizado.');
    }

    public function resendVerification(): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->authService->resendVerification());

        if (! $response['ok']) {
            return redirect()->to(site_url('profile'))->with('error', $this->firstMessage($response, 'No se pudo reenviar la verificacion.'));
        }

        return redirect()->to(site_url('profile'))->with('success', 'Te reenviamos el correo de verificacion.');
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
