<?php

declare(strict_types=1);

namespace App\Modules\Iam\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Iam\Requests\PermissionStoreRequest;
use App\Modules\Iam\Requests\PermissionUpdateRequest;
use App\Modules\Iam\Services\PermissionApiServiceInterface;
use App\Modules\Iam\Support\IamLookups;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class PermissionController extends BaseWebController
{
    protected PermissionApiServiceInterface $permissionService;
    protected IamLookups $lookups;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->permissionService = service('permissionApiService');
        $this->lookups           = new IamLookups();
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
        $tableState = $this->resolveTableState([], ['code', 'resource', 'action', 'application_id', 'created_at']);
        $params     = $this->buildTableApiParams($tableState);
        $response   = $this->safeApiCall(fn () => $this->permissionService->list($params));

        unset($response['raw']);

        return $this->passthroughApiJsonResponse($response);
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn () => $this->permissionService->get($id));

        if (! ($response['ok'] ?? false)) {
            return $this->render('iam/permissions/show', [
                'title'      => lang('Iam.permissions_details'),
                'permission' => [],
                'error'      => $this->firstMessage($response, lang('Iam.permissions_not_found')),
            ]);
        }

        $permission = $this->extractData($response);

        return $this->render('iam/permissions/show', [
            'title'      => lang('Iam.permissions_details'),
            'permission' => $permission,
        ]);
    }

    public function create(): string
    {
        return $this->render('iam/permissions/create', [
            'title'        => lang('Iam.permissions_create'),
            'applications' => $this->lookups->applications(),
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
            'title'        => lang('Iam.permissions_edit'),
            'item'         => $this->extractData($response),
            'applications' => $this->lookups->applications(),
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
