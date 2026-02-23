<?php

namespace App\Controllers;

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
            ['created_at', 'email', 'role', 'status', 'first_name', 'last_name'],
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
        if (! $this->validate([
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
            'email'      => 'required|valid_email',
            'role'       => 'required|in_list[user,admin]',
        ])) {
            return $this->failValidation();
        }

        $payload = [
            'first_name' => (string) $this->request->getPost('first_name'),
            'last_name'  => (string) $this->request->getPost('last_name'),
            'email'      => (string) $this->request->getPost('email'),
            'role'       => (string) $this->request->getPost('role'),
            'client_base_url' => $this->clientBaseUrl(),
        ];

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
        if (! $this->validate([
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
            'email'      => 'required|valid_email',
            'role'       => 'required|in_list[user,admin]',
        ])) {
            return $this->failValidation();
        }

        $payload = [
            'first_name' => (string) $this->request->getPost('first_name'),
            'last_name'  => (string) $this->request->getPost('last_name'),
            'role'       => (string) $this->request->getPost('role'),
        ];

        $email = trim((string) $this->request->getPost('email'));
        $originalEmail = trim((string) $this->request->getPost('original_email'));

        if ($originalEmail === '' || mb_strtolower($email) !== mb_strtolower($originalEmail)) {
            $payload['email'] = $email;
        }

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
