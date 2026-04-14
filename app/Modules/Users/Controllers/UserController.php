<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;
use App\Controllers\BaseWebController;

use App\Modules\Users\Requests\UserStoreRequest;
use App\Modules\Users\Requests\UserUpdateRequest;
use App\Services\CatalogApiService;
use App\Modules\Users\Services\UserApiService;
use App\Support\CatalogOptions;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class UserController extends BaseWebController
{
    protected UserApiService $userService;
    protected CatalogApiService $catalogService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->userService = service('userApiService');
        $this->catalogService = service('catalogApiService');
    }

    public function index(): string
    {
        $catalogs = $this->resolveCatalogs($this->catalogService);

        return $this->render('users/index', [
            'title'         => lang('Users.title'),
            'roleOptions'   => CatalogOptions::options($catalogs, 'users.roles', $this->defaultRoleOptions()),
            'statusOptions' => CatalogOptions::options($catalogs, 'users.statuses', $this->defaultStatusOptions()),
            'limitOptions'  => CatalogOptions::limitOptions($catalogs),
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

        return $this->renderResourceShow('users/show', lang('Users.details'), 'user', $response, lang('Users.not_found'));
    }

    public function create(): string
    {
        $catalogs = $this->resolveCatalogs($this->catalogService);

        return $this->render('users/create', [
            'title'       => lang('Users.create'),
            'roleOptions' => CatalogOptions::options($catalogs, 'users.roles', $this->defaultRoleOptions()),
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

        $response = $this->safeApiCall(fn() => $this->userService->create($payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.create_failed'));
        }

        return redirect()->to(route_to('admin.users'))->with('success', lang('Users.create_success'));
    }

    public function edit(string $id): string|RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->userService->get($id));

        if (! $response['ok']) {
            return redirect()->to(route_to('admin.users'))->with('error', lang('Users.not_found'));
        }

        $catalogs = $this->resolveCatalogs($this->catalogService);

        return $this->render('users/edit', [
            'title'       => lang('Users.edit_user'),
            'editUser'    => $this->extractData($response),
            'roleOptions' => CatalogOptions::options($catalogs, 'users.roles', $this->defaultRoleOptions()),
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
            return $this->failApi($response, lang('Users.update_failed'));
        }

        return redirect()->to(route_to('admin.users.show', $id))->with('success', lang('Users.update_success'));
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->userService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.delete_failed'), route_to('admin.users'), false);
        }

        return redirect()->to(route_to('admin.users'))->with('success', lang('Users.delete_success'));
    }

    public function approve(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->userService->approve($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Users.approve_failed'), route_to('admin.users.show', $id), false);
        }

        return redirect()->to(route_to('admin.users.show', $id))->with('success', lang('Users.approve_success'));
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function defaultRoleOptions(): array
    {
        return [
            ['value' => 'user', 'label' => lang('Users.user_role')],
            ['value' => 'admin', 'label' => lang('Users.admin_role')],
            ['value' => 'superadmin', 'label' => lang('Users.super_admin_role')],
        ];
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
