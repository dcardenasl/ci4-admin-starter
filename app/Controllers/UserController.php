<?php

namespace App\Controllers;

use App\Requests\User\UserStoreRequest;
use App\Requests\User\UserUpdateRequest;
use App\Services\UserApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class UserController extends BaseWebController
{
    protected UserApiService $userService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->userService = service('userApiService');
    }

    public function index(): string
    {
        return $this->render('users/index', [
            'title'      => lang('Users.title'),
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            ['status', 'role'],
            ['createdAt', 'email', 'role', 'status', 'firstName', 'lastName'],
            fn(array $params) => $this->userService->list($params),
        );
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn() => $this->userService->get($id));

        return $this->renderResourceShow('users/show', lang('Users.details'), 'user', $response, lang('Users.notFound'));
    }

    public function create(): string
    {
        return $this->render('users/create', [
            'title' => lang('Users.create'),
        ]);
    }

    public function store(): RedirectResponse
    {
        /** @var UserStoreRequest $request */
        $request = service('formRequest', UserStoreRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();
        $payload['client_base_url'] = $this->clientBaseUrl();

        $response = $this->safeApiCall(fn() => $this->userService->create($payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.createFailed'));
        }

        return redirect()->to(site_url('admin/users'))->with('success', lang('Users.createSuccess'));
    }

    public function edit(string $id): string
    {
        $response = $this->safeApiCall(fn() => $this->userService->get($id));

        return $this->render('users/edit', [
            'title'    => lang('Users.editUser'),
            'editUser' => $this->extractData($response),
        ]);
    }

    public function update(string $id): RedirectResponse
    {
        /** @var UserUpdateRequest $request */
        $request = service('formRequest', UserUpdateRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();

        $response = $this->safeApiCall(fn() => $this->userService->update($id, $payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.updateFailed'));
        }

        return redirect()->to(site_url('admin/users/' . $id))->with('success', lang('Users.updateSuccess'));
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->userService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.deleteFailed'), site_url('admin/users'), false);
        }

        return redirect()->to(site_url('admin/users'))->with('success', lang('Users.deleteSuccess'));
    }

    public function approve(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->userService->approve($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.approveFailed'), site_url('admin/users/' . $id), false);
        }

        return redirect()->to(site_url('admin/users/' . $id))->with('success', lang('Users.approveSuccess'));
    }

}
