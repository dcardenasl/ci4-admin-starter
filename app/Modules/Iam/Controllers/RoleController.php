<?php

declare(strict_types=1);

namespace App\Modules\Iam\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Iam\Requests\RoleStoreRequest;
use App\Modules\Iam\Requests\RoleUpdateRequest;
use App\Modules\Iam\Services\RoleApiServiceInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class RoleController extends BaseWebController
{
    protected RoleApiServiceInterface $roleService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->roleService = service('roleApiService');
    }

    public function index(): string
    {
        return $this->render('iam/roles/index', [
            'title'        => lang('Iam.roles_title'),
            'limitOptions' => [10, 25, 50, 100],
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            [],
            ['name', 'created_at'],
            fn (array $params) => $this->roleService->list($params),
        );
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn () => $this->roleService->get($id));

        return $this->renderResourceShow(
            'iam/roles/show',
            lang('Iam.roles_details'),
            'role',
            $response,
            lang('Iam.roles_not_found'),
        );
    }

    public function create(): string
    {
        return $this->render('iam/roles/create', [
            'title' => lang('Iam.roles_create'),
        ]);
    }

    public function store(): RedirectResponse
    {
        /** @var RoleStoreRequest $request */
        $request = service('formRequest', RoleStoreRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $response = $this->safeApiCall(fn () => $this->roleService->create($request->payload()));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.roles_create_failed'));
        }

        return redirect()->to(route_to('admin.iam.roles'))->with('success', lang('Iam.roles_create_success'));
    }

    public function edit(string $id): string|RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->roleService->get($id));
        if (! $response['ok']) {
            return $this->withError(lang('Iam.roles_not_found'), route_to('admin.iam.roles'));
        }

        return $this->render('iam/roles/edit', [
            'title' => lang('Iam.roles_edit'),
            'item'  => $this->extractData($response),
        ]);
    }

    public function update(string $id): RedirectResponse
    {
        /** @var RoleUpdateRequest $request */
        $request = service('formRequest', RoleUpdateRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $response = $this->safeApiCall(fn () => $this->roleService->update($id, $request->payload()));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.roles_update_failed'));
        }

        return redirect()->to(route_to('admin.iam.roles'))->with('success', lang('Iam.roles_update_success'));
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->roleService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.roles_delete_failed'), route_to('admin.iam.roles'), false);
        }

        return redirect()->to(route_to('admin.iam.roles'))->with('success', lang('Iam.roles_delete_success'));
    }
}
