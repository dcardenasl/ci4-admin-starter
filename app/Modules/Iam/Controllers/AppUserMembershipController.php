<?php

declare(strict_types=1);

namespace App\Modules\Iam\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Iam\Requests\AppUserMembershipStoreRequest;
use App\Modules\Iam\Requests\AppUserMembershipUpdateRequest;
use App\Modules\Iam\Services\AppUserMembershipApiServiceInterface;
use App\Modules\Iam\Services\RoleApiServiceInterface;
use App\Modules\Iam\Support\IamLookups;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AppUserMembershipController extends BaseWebController
{
    protected AppUserMembershipApiServiceInterface $appUserMembershipService;
    protected RoleApiServiceInterface $roleService;
    protected IamLookups $lookups;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->appUserMembershipService = service('appUserMembershipApiService');
        $this->roleService              = service('roleApiService');
        $this->lookups                  = new IamLookups();
    }

    public function index(): string
    {
        return $this->render('iam/memberships/index', [
            'title'        => lang('Iam.app_user_memberships_title'),
            'limitOptions' => [10, 25, 50, 100],
        ]);
    }

    public function data(): ResponseInterface
    {
        $tableState = $this->resolveTableState([], ['user_id', 'application_id', 'status', 'created_at']);
        $params     = $this->buildTableApiParams($tableState);
        $response   = $this->safeApiCall(fn () => $this->appUserMembershipService->list($params));

        $appNames   = $this->lookups->applicationNames();
        $userLabels = $this->lookups->userLabels();
        $body       = is_array($response['data'] ?? null) ? $response['data'] : [];
        if (isset($body['data']) && is_array($body['data'])) {
            $body['data'] = array_map(static function (array $row) use ($appNames, $userLabels): array {
                $appId               = (int) ($row['application_id'] ?? 0);
                $userId              = (int) ($row['user_id'] ?? 0);
                $row['application_name'] = $appNames[$appId] ?? null;
                $row['user_label']       = $userLabels[$userId] ?? null;

                return $row;
            }, $body['data']);
        }

        $response['data'] = $body;
        unset($response['raw']);

        return $this->passthroughApiJsonResponse($response);
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn () => $this->appUserMembershipService->get($id));

        if (! ($response['ok'] ?? false)) {
            return $this->render('iam/memberships/show', [
                'title'             => lang('Iam.app_user_memberships_details'),
                'appUserMembership' => [],
                'error'             => $this->firstMessage($response, lang('Iam.app_user_memberships_not_found')),
            ]);
        }

        $assignedResponse = $this->safeApiCall(fn () => $this->appUserMembershipService->listRoles($id));
        $allRolesResponse = $this->safeApiCall(fn () => $this->roleService->list(['limit' => 200]));

        $assignedRoles = $this->extractItems($assignedResponse);
        $allRoles      = $this->extractItems($allRolesResponse);
        $assignedIds   = array_map(static fn (array $r): int => (int) ($r['id'] ?? 0), $assignedRoles);

        $membership                     = $this->extractData($response);
        $appId                          = (int) ($membership['application_id'] ?? 0);
        $userId                         = (int) ($membership['user_id'] ?? 0);
        $membership['application_name'] = $this->lookups->applicationNames()[$appId] ?? null;
        $membership['user_label']       = $this->lookups->userLabels()[$userId] ?? null;

        return $this->render('iam/memberships/show', [
            'title'             => lang('Iam.app_user_memberships_details'),
            'appUserMembership' => $membership,
            'allRoles'          => $allRoles,
            'assignedRoleIds'   => $assignedIds,
        ]);
    }

    public function create(): string
    {
        return $this->render('iam/memberships/create', [
            'title'        => lang('Iam.app_user_memberships_create'),
            'applications' => $this->lookups->applications(),
            'users'        => $this->lookups->users(),
        ]);
    }

    public function store(): RedirectResponse
    {
        /** @var AppUserMembershipStoreRequest $request */
        $request = service('formRequest', AppUserMembershipStoreRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $response = $this->safeApiCall(fn () => $this->appUserMembershipService->create($request->payload()));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.app_user_memberships_create_failed'));
        }

        return redirect()->to(route_to('admin.iam.memberships'))->with('success', lang('Iam.app_user_memberships_create_success'));
    }

    public function edit(string $id): string|RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->appUserMembershipService->get($id));
        if (! $response['ok']) {
            return $this->withError(lang('Iam.app_user_memberships_not_found'), route_to('admin.iam.memberships'));
        }

        return $this->render('iam/memberships/edit', [
            'title'        => lang('Iam.app_user_memberships_edit'),
            'item'         => $this->extractData($response),
            'applications' => $this->lookups->applications(),
            'users'        => $this->lookups->users(),
        ]);
    }

    public function update(string $id): RedirectResponse
    {
        /** @var AppUserMembershipUpdateRequest $request */
        $request = service('formRequest', AppUserMembershipUpdateRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $response = $this->safeApiCall(fn () => $this->appUserMembershipService->update($id, $request->payload()));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.app_user_memberships_update_failed'));
        }

        return redirect()->to(route_to('admin.iam.memberships'))->with('success', lang('Iam.app_user_memberships_update_success'));
    }

    public function delete(string $id): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->appUserMembershipService->delete($id));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.app_user_memberships_delete_failed'), route_to('admin.iam.memberships'), false);
        }

        return redirect()->to(route_to('admin.iam.memberships'))->with('success', lang('Iam.app_user_memberships_delete_success'));
    }

    public function attachRoles(string $id): RedirectResponse
    {
        $rawIds = $this->request->getPost('role_ids');
        $ids    = is_array($rawIds) ? array_values(array_map('intval', $rawIds)) : [];

        if ($ids === []) {
            return redirect()->to(route_to('admin.iam.memberships.show', $id))
                ->with('error', lang('Iam.roles_attach_select_required'));
        }

        $response = $this->safeApiCall(fn () => $this->appUserMembershipService->attachRoles($id, $ids));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.roles_attach_failed'), route_to('admin.iam.memberships.show', $id), false);
        }

        return redirect()->to(route_to('admin.iam.memberships.show', $id))
            ->with('success', lang('Iam.roles_attach_success'));
    }

    public function detachRole(string $id, string $roleId): RedirectResponse
    {
        $response = $this->safeApiCall(fn () => $this->appUserMembershipService->detachRole($id, $roleId));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Iam.roles_detach_failed'), route_to('admin.iam.memberships.show', $id), false);
        }

        return redirect()->to(route_to('admin.iam.memberships.show', $id))
            ->with('success', lang('Iam.roles_detach_success'));
    }
}
