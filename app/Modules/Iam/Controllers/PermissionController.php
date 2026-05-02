<?php

declare(strict_types=1);

namespace App\Modules\Iam\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Iam\Requests\PermissionStoreRequest;
use App\Modules\Iam\Requests\PermissionUpdateRequest;
use App\Modules\Iam\Services\PermissionApiServiceInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class PermissionController extends BaseWebController
{
    protected PermissionApiServiceInterface $permissionService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->permissionService = service('permissionApiService');
    }

    public function index(): string
    {
        return $this->render('iam/permissions/index', [
            'title'        => lang('Iam.permissions_title'),
            'limitOptions' => [10, 25, 50, 100],
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            [],
            ['name', 'created_at'],
            fn (array $params) => $this->permissionService->list($params),
        );
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn () => $this->permissionService->get($id));

        return $this->renderResourceShow(
            'iam/permissions/show',
            lang('Iam.permissions_details'),
            'permission',
            $response,
            lang('Iam.permissions_not_found'),
        );
    }

    public function create(): string
    {
        return $this->render('iam/permissions/create', [
            'title' => lang('Iam.permissions_create'),
        ]);
    }

    public function store(): RedirectResponse
    {
        /** @var PermissionStoreRequest $request */
        $request = service('formRequest', PermissionStoreRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $response = $this->safeApiCall(fn () => $this->permissionService->create($request->payload()));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.permissions_create_failed'));
        }

        return redirect()->to(route_to('admin.iam.permissions'))->with('success', lang('Iam.permissions_create_success'));
    }

    public function edit(string $id): string|RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->permissionService->get($id));
        if (! $response['ok']) {
            return $this->withError(lang('Iam.permissions_not_found'), route_to('admin.iam.permissions'));
        }

        return $this->render('iam/permissions/edit', [
            'title' => lang('Iam.permissions_edit'),
            'item'  => $this->extractData($response),
        ]);
    }

    public function update(string $id): RedirectResponse
    {
        /** @var PermissionUpdateRequest $request */
        $request = service('formRequest', PermissionUpdateRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $response = $this->safeApiCall(fn () => $this->permissionService->update($id, $request->payload()));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.permissions_update_failed'));
        }

        return redirect()->to(route_to('admin.iam.permissions'))->with('success', lang('Iam.permissions_update_success'));
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->permissionService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.permissions_delete_failed'), route_to('admin.iam.permissions'), false);
        }

        return redirect()->to(route_to('admin.iam.permissions'))->with('success', lang('Iam.permissions_delete_success'));
    }
}
