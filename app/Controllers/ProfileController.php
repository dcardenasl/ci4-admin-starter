<?php

namespace App\Controllers;

use App\Services\AuthApiService;
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

    public function update()
    {
        $payload = [
            'first_name' => (string) $this->request->getPost('first_name'),
            'last_name'  => (string) $this->request->getPost('last_name'),
            'avatar_url' => (string) $this->request->getPost('avatar_url'),
        ];

        $response = $this->authService->updateProfile($payload);

        if (! $response['ok']) {
            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, 'No se pudo actualizar el perfil.'));
        }

        $this->refreshUserSession();

        return redirect()->to('/profile')->with('success', 'Perfil actualizado.');
    }

    public function changePassword()
    {
        $payload = [
            'current_password'      => (string) $this->request->getPost('current_password'),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];

        $response = $this->authService->changePassword($payload);

        if (! $response['ok']) {
            return redirect()->back()->with('error', $this->firstMessage($response, 'No se pudo cambiar el password.'));
        }

        return redirect()->to('/profile')->with('success', 'Password actualizado.');
    }

    public function resendVerification()
    {
        $response = $this->authService->resendVerification();

        if (! $response['ok']) {
            return redirect()->to('/profile')->with('error', $this->firstMessage($response, 'No se pudo reenviar la verificacion.'));
        }

        return redirect()->to('/profile')->with('success', 'Te reenviamos el correo de verificacion.');
    }

    protected function refreshUserSession(): void
    {
        $me = $this->authService->me();

        if (! $me['ok']) {
            return;
        }

        $payload = $me['data'] ?? [];
        $user = $payload['data'] ?? $payload;

        if (is_array($user)) {
            session()->set('user', $user);
        }
    }

    protected function firstMessage(array $response, string $fallback): string
    {
        $messages = $response['messages'] ?? [];

        if (is_array($messages) && isset($messages[0])) {
            return (string) $messages[0];
        }

        return $fallback;
    }
}
