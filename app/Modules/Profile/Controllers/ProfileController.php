<?php

declare(strict_types=1);

namespace App\Modules\Profile\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Files\Services\FileApiServiceInterface;
use App\Modules\Profile\Requests\ProfileUpdateRequest;
use App\Modules\Profile\Services\ProfileApiServiceInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ProfileController extends BaseWebController
{
    protected ProfileApiServiceInterface $profileService;
    protected FileApiServiceInterface $fileService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->profileService = service('profileApiService');
        $this->fileService    = service('fileApiService');
    }

    public function index(): string
    {
        $this->refreshUserSession();
        $user = session('user') ?? [];
        $isAdmin = has_permission('users.write');

        return $this->render('profile/index', [
            'title'   => lang('Profile.title'),
            'user'    => $user,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function update(): RedirectResponse
    {
        $sessionUser = session('user') ?? [];

        if (! has_permission('users.write')) {
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

        $response = $this->safeApiCall(fn () => $this->profileService->update((string) $userId, $payload));

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

        $response = $this->safeApiCall(fn () => $this->profileService->forgotPassword(
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
        $response = $this->safeApiCall(fn () => $this->profileService->resendVerification([
            'client_base_url' => $this->clientBaseUrl(),
        ]));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Profile.resend_failed'), route_to('profile'), false);
        }

        return redirect()->to(route_to('profile'))->with('success', lang('Profile.resend_success'));
    }

    public function updateAvatar(): RedirectResponse
    {
        $file = $this->request instanceof \CodeIgniter\HTTP\IncomingRequest
            ? $this->request->getFile('avatar')
            : null;

        if ($file === null || ! $file->isValid()) {
            return redirect()->to(route_to('profile'))->with('error', lang('Profile.avatar_invalid_file'));
        }

        $mime     = $file->getMimeType();
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (! in_array($mime, $allowed, true)) {
            return redirect()->to(route_to('profile'))->with('error', lang('Profile.avatar_invalid_file'));
        }

        $uploadResponse = $this->safeApiCall(fn () => $this->fileService->upload(
            'file',
            $file->getTempName(),
            $file->getName(),
            $mime,
            ['visibility' => 'public'],
        ));

        if (! ($uploadResponse['ok'] ?? false)) {
            return $this->failApi($uploadResponse, lang('Profile.avatar_update_failed'), route_to('profile'), false);
        }

        $fileData  = $this->extractData($uploadResponse);
        $avatarUrl = (string) ($fileData['url'] ?? '');

        if ($avatarUrl === '') {
            return redirect()->to(route_to('profile'))->with('error', lang('Profile.avatar_update_failed'));
        }

        $sessionUser = session('user') ?? [];
        $userId      = $sessionUser['id'] ?? null;
        if (! is_scalar($userId) || (string) $userId === '') {
            return redirect()->to(route_to('profile'))->with('error', lang('Profile.avatar_update_failed'));
        }

        $updateResponse = $this->safeApiCall(fn () => $this->profileService->update((string) $userId, ['avatar_url' => $avatarUrl]));

        if (! ($updateResponse['ok'] ?? false)) {
            return $this->failApi($updateResponse, lang('Profile.avatar_update_failed'), route_to('profile'), false);
        }

        $this->refreshUserSession();

        return redirect()->to(route_to('profile'))->with('success', lang('Profile.avatar_update_success'));
    }

    protected function refreshUserSession(): void
    {
        $me = $this->safeApiCall(fn () => $this->profileService->me());

        if (! $me['ok']) {
            return;
        }

        $user = $this->extractData($me);

        if (! empty($user)) {
            session()->set('user', $user);
        }
    }
}
