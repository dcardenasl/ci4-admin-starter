<?php

namespace App\Controllers;

use App\Services\AuthApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AuthController extends BaseWebController
{
    protected AuthApiService $authService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->authService = service('authApiService');
    }

    public function login(): string|RedirectResponse
    {
        if ($this->session->has('access_token')) {
            return redirect()->to(site_url('dashboard'));
        }

        return $this->renderAuth('auth/login', [
            'title'    => 'Iniciar sesion',
            'subtitle' => 'Accede a tu cuenta',
        ]);
    }

    public function attemptLogin(): RedirectResponse
    {
        if (! $this->validate([
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = [
            'email'    => (string) $this->request->getPost('email'),
            'password' => (string) $this->request->getPost('password'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->login($payload));

        if (! $response['ok']) {
            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, 'Credenciales invalidas.'));
        }

        $data = $this->extractData($response);
        $this->persistAuthSession($data);

        return redirect()->to(site_url('dashboard'))->with('success', 'Sesion iniciada correctamente.');
    }

    public function register(): string
    {
        return $this->renderAuth('auth/register', [
            'title'    => 'Crear cuenta',
            'subtitle' => 'Completa tu registro',
        ]);
    }

    public function attemptRegister(): RedirectResponse
    {
        if (! $this->validate([
            'first_name'            => 'required|min_length[2]|max_length[100]',
            'last_name'             => 'required|min_length[2]|max_length[100]',
            'email'                 => 'required|valid_email',
            'password'              => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = [
            'first_name'            => (string) $this->request->getPost('first_name'),
            'last_name'             => (string) $this->request->getPost('last_name'),
            'email'                 => (string) $this->request->getPost('email'),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->register($payload));

        if (! $response['ok']) {
            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, 'No fue posible completar el registro.'));
        }

        return redirect()->to(site_url('login'))->with('success', 'Registro completado. Revisa tu correo para verificar tu cuenta.');
    }

    public function forgotPassword(): string
    {
        return $this->renderAuth('auth/forgot_password', [
            'title'    => 'Recuperar password',
            'subtitle' => 'Te enviaremos un enlace por correo',
        ]);
    }

    public function attemptForgotPassword(): RedirectResponse
    {
        if (! $this->validate([
            'email' => 'required|valid_email',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email = (string) $this->request->getPost('email');
        $response = $this->safeApiCall(fn () => $this->authService->forgotPassword($email));

        if (! $response['ok']) {
            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, 'No fue posible procesar la solicitud.'));
        }

        return redirect()->to(site_url('login'))->with('success', 'Te enviamos instrucciones para recuperar tu password.');
    }

    public function resetPassword(): string
    {
        return $this->renderAuth('auth/reset_password', [
            'title'    => 'Restablecer password',
            'subtitle' => 'Define una nueva credencial',
            'token'    => (string) $this->request->getGet('token'),
        ]);
    }

    public function attemptResetPassword(): RedirectResponse
    {
        if (! $this->validate([
            'token'                 => 'required',
            'password'              => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = [
            'token'                 => (string) $this->request->getPost('token'),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->resetPassword($payload));

        if (! $response['ok']) {
            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, 'No fue posible cambiar tu password.'));
        }

        return redirect()->to(site_url('login'))->with('success', 'Password actualizado correctamente.');
    }

    public function verifyEmail(): string
    {
        $token = (string) $this->request->getGet('token');
        $response = $this->safeApiCall(fn () => $this->authService->verifyEmail($token));

        return $this->renderAuth('auth/verify_email', [
            'title'    => 'Verificacion de correo',
            'subtitle' => 'Resultado de validacion',
            'verified' => $response['ok'],
            'message'  => $this->firstMessage($response, $response['ok'] ? 'Tu correo fue verificado.' : 'No se pudo verificar tu correo.'),
        ]);
    }

    public function logout(): RedirectResponse
    {
        if ($this->session->has('access_token')) {
            $this->safeApiCall(fn () => $this->authService->logout());
        }

        $this->session->destroy();

        return redirect()->to(site_url('login'))->with('success', 'Sesion finalizada.');
    }

    protected function persistAuthSession(array $data): void
    {
        $this->session->set('access_token', $data['access_token'] ?? null);
        $this->session->set('refresh_token', $data['refresh_token'] ?? null);
        $this->session->set('token_expires_at', time() + (int) ($data['expires_in'] ?? 3600));
        $this->session->set('user', $data['user'] ?? []);
    }
}
