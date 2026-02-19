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
        $tableState = $this->resolveTableState(
            ['status', 'role'],
            ['created_at', 'email', 'role', 'status', 'first_name', 'last_name'],
        );

        $response = $this->safeApiCall(fn() => $this->userService->list($this->buildTableApiParams($tableState)));

        return $this->passthroughApiJsonResponse($response);
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn() => $this->userService->get($id));

        if (! $response['ok']) {
            return $this->render('users/show', [
                'title' => lang('Users.details'),
                'user'  => [],
                'error' => $this->firstMessage($response, lang('Users.notFound')),
            ]);
        }

        return $this->render('users/show', [
            'title' => lang('Users.details'),
            'user'  => $this->extractData($response),
        ]);
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
            return redirect()->back()->withInput()->with('fieldErrors', $this->validator->getErrors());
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
            $fieldErrors = $this->getFieldErrors($response);

            if (! empty($fieldErrors)) {
                return $this->withFieldErrors($fieldErrors);
            }

            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, lang('Users.createFailed')));
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
            return redirect()->back()->withInput()->with('fieldErrors', $this->validator->getErrors());
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
            $fieldErrors = $this->getFieldErrors($response);

            if (! empty($fieldErrors)) {
                return $this->withFieldErrors($fieldErrors);
            }

            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, lang('Users.updateFailed')));
        }

        return redirect()->to(site_url('admin/users/' . $id))->with('success', lang('Users.updateSuccess'));
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->userService->delete($id));

        if (! $response['ok']) {
            return redirect()->to(site_url('admin/users'))->with('error', $this->firstMessage($response, lang('Users.deleteFailed')));
        }

        return redirect()->to(site_url('admin/users'))->with('success', lang('Users.deleteSuccess'));
    }

    public function approve(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->userService->approve($id));

        if (! $response['ok']) {
            return redirect()->to(site_url('admin/users/' . $id))->with('error', $this->firstMessage($response, lang('Users.approveFailed')));
        }

        return redirect()->to(site_url('admin/users/' . $id))->with('success', lang('Users.approveSuccess'));
    }

}
