<?php

declare(strict_types=1);

namespace App\Modules\Iam\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Iam\Services\RoleApiServiceInterface;
use App\Modules\Iam\Services\RoleMatrixApiServiceInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class RolePermissionsController extends BaseWebController
{
    private RoleMatrixApiServiceInterface $matrixService;
    private RoleApiServiceInterface $roleService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->matrixService = service('roleMatrixApiService');
        $this->roleService = service('roleApiService');
    }

    public function index(): string
    {
        $response = $this->safeApiCall(fn () => $this->matrixService->matrix());
        $matrix = $this->extractData($response);
        $tab = $this->request->getGet('tab');
        $activeTab = is_string($tab) ? $tab : '';

        return $this->render('iam/role_permissions/index', [
            'title'       => lang('Iam.role_permissions_title'),
            'applications' => $matrix['applications'] ?? [],
            'roles'       => $matrix['roles'] ?? [],
            'assignments' => $matrix['assignments'] ?? [],
            'activeTab'   => $activeTab,
            'error'       => ($response['ok'] ?? false) ? null : $this->firstMessage($response, lang('Iam.role_permissions_load_failed')),
        ]);
    }

    public function save(string $roleId): RedirectResponse
    {
        $rawIds = $this->request->getPost('permission_ids');
        $ids = is_array($rawIds)
            ? array_values(array_unique(array_filter(array_map('intval', $rawIds), static fn (int $id): bool => $id > 0)))
            : [];

        $response = $this->safeApiCall(fn () => $this->roleService->update($roleId, ['permission_ids' => $ids]));
        if (! ($response['ok'] ?? false)) {
            return $this->failApi($response, lang('Iam.role_permissions_save_failed'), route_to('admin.iam.role_permissions') . '?tab=' . $roleId, false);
        }

        service('permissionsSessionRefresher')->forceRefresh();

        return redirect()
            ->to(route_to('admin.iam.role_permissions') . '?tab=' . $roleId)
            ->with('success', lang('Iam.role_permissions_save_success'));
    }
}
