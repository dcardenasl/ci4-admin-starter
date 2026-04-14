<?php

declare(strict_types=1);

namespace AppModulesProfileControllers;
use App\Controllers\BaseWebController;

use AppModulesProfileRequestsProfile\ProfileUpdateRequest;
use AppModulesProfileServicesProfileApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ProfileController extends BaseWebController
{
    protected ProfileApiService $profileService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->profileService = service('profileApiService');
    }

    public function index(): string
    {
        $this->refreshUserSession();
        $user = session('user') ?? [];
        $isAdmin = has_admin_access(is_scalar($user['role'] ?? null) ? (string) $user['role'] : null);

        return $this->render('profile/index', [
            'title'   => lang('Profile.title'),
            'user'    => $user,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function update(): RedirectResponse
    {
        $sessionUser = session('user') ?? [];
        $isAdmin = has_admin_access(is_scalar($sessionUser['role'] ?? null) ? (string) $sessionUser['role'] : null);

        if (! $isAdmin) {
            return redirect()->to(route_to('profile'))->with('error', lang('Profile.update_not_allowed'));
        }

        /** @var ProfileUpdateRequest $request */
        $request = service('formRequest', ProfileUpdateRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();

        $userId = $sessionUser['id'] ?? null;
        if (! is_scalar($userId) || (string) $userId === '') {
            return redirect()->to(route_to('profile'))->with('error', lang('Profile.update_failed'));
        }

        $response = $this->safeApiCall(fn() => $this->profileService->update((string) $userId, $payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Profile.update_failed'));
        }

        $this->refreshUserSession();

        return redirect()->to(route_to('profile'))->with('success', lang('Profile.update_success'));
    }

    public function requestPasswordReset(): RedirectResponse
    {
        $email = trim((string) (session('user.email') ?? ''));
        if ($email === '') {
            return redirect()->to(route_to('profile'))->with('error', lang('Profile.password_reset_failed'));
        }

        $response = $this->safeApiCall(fn() => $this->profileService->forgotPassword(
            $email,
            $this->clientBaseUrl(),
        ));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Profile.password_reset_failed'), route_to('profile'), false);
        }

        return redirect()->to(route_to('profile'))->with('success', lang('Profile.password_reset_sent'));
    }

    public function resendVerification(): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->profileService->resendVerification([
            'client_base_url' => $this->clientBaseUrl(),
        ]));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Profile.resend_failed'), route_to('profile'), false);
        }

        return redirect()->to(route_to('profile'))->with('success', lang('Profile.resend_success'));
    }

    protected function refreshUserSession(): void
    {
        $me = $this->safeApiCall(fn() => $this->profileService->me());

        if (! $me['ok']) {
            return;
        }

        $user = $this->extractData($me);

        if (! empty($user)) {
            session()->set('user', $user);
        }
    }
}
