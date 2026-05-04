<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Users\Requests\UserStoreRequest;
use App\Modules\Users\Requests\UserUpdateRequest;
use App\Modules\Users\Services\UserApiServiceInterface;
use App\Support\CatalogOptions;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class UserController extends BaseWebController
{
    protected UserApiServiceInterface $userService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->userService = service('userApiService');
    }

    public function index(): string
    {
        return $this->render('users/index', [
            'title'         => lang('Users.title'),
            'statusOptions' => CatalogOptions::options([], 'users.statuses', $this->defaultStatusOptions()),
            'limitOptions'  => CatalogOptions::limitOptions([]),
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            ['status'],
            ['created_at', 'email', 'status', 'first_name', 'last_name'],
            fn (array $params) => $this->userService->list($params),
        );
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn () => $this->userService->get($id));

        if (! ($response['ok'] ?? false)) {
            return $this->render('users/show', [
                'title' => lang('Users.details'),
                'user'  => [],
                'roles' => [],
                'error' => $this->firstMessage($response, lang('Users.not_found')),
            ]);
        }

        $user = $this->extractData($response);

        return $this->render('users/show', [
            'title' => lang('Users.details'),
            'user'  => $user,
            'roles' => is_array($user['roles'] ?? null) ? $user['roles'] : [],
        ]);
    }

    public function create(): string
    {
        return $this->render('users/create', [
            'title'           => lang('Users.create'),
            'assignableRoles' => $this->fetchAssignableRoles(),
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

        $response = $this->safeApiCall(fn () => $this->userService->create($payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.create_failed'));
        }

        return redirect()->to(route_to('admin.users'))->with('success', lang('Users.create_success'));
    }

    public function edit(string $id): string|RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->userService->get($id));

        if (! $response['ok']) {
            return redirect()->to(route_to('admin.users'))->with('error', lang('Users.not_found'));
        }

        $user = $this->extractData($response);

        return $this->render('users/edit', [
            'title'           => lang('Users.edit_user'),
            'editUser'        => $user,
            'currentRoleIds'  => array_map(
                static fn (array $r) => (int) ($r['id'] ?? 0),
                is_array($user['roles'] ?? null) ? $user['roles'] : []
            ),
            'assignableRoles' => $this->fetchAssignableRoles(),
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

        $response = $this->safeApiCall(fn () => $this->userService->update($id, $payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.update_failed'));
        }

        return redirect()->to(route_to('admin.users.show', $id))->with('success', lang('Users.update_success'));
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->userService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.delete_failed'), route_to('admin.users'), false);
        }

        return redirect()->to(route_to('admin.users'))->with('success', lang('Users.delete_success'));
    }

    public function approve(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->userService->approve($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.approve_failed'), route_to('admin.users.show', $id), false);
        }

        return redirect()->to(route_to('admin.users.show', $id))->with('success', lang('Users.approve_success'));
    }

    /**
     * @return array<int, array{id:int, code:string, name:string}>
     */
    private function fetchAssignableRoles(): array
    {
        $response = $this->safeApiCall(fn () => $this->userService->assignableRoles());
        if (! ($response['ok'] ?? false)) {
            return [];
        }

        $items = $this->extractItems($response);
        $items = $items === [] ? $this->extractData($response) : $items;
        if (! is_array($items)) {
            return [];
        }

        $roles = [];
        foreach ($items as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }
            $roles[] = [
                'id'   => (int) $row['id'],
                'code' => (string) ($row['code'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        return $roles;
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function defaultStatusOptions(): array
    {
        return [
            ['value' => 'active', 'label' => lang('App.yes')],
            ['value' => 'inactive', 'label' => lang('App.no')],
            ['value' => 'pending_approval', 'label' => lang('Users.pending_approval')],
            ['value' => 'invited', 'label' => lang('Users.invited')],
        ];
    }

}
