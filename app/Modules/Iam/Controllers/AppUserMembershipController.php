<?php

declare(strict_types=1);

namespace App\Modules\Iam\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Iam\Requests\AppUserMembershipStoreRequest;
use App\Modules\Iam\Requests\AppUserMembershipUpdateRequest;
use App\Modules\Iam\Services\AppUserMembershipApiServiceInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AppUserMembershipController extends BaseWebController
{
    protected AppUserMembershipApiServiceInterface $appUserMembershipService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->appUserMembershipService = service('appUserMembershipApiService');
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
        return $this->tableDataResponse(
            [],
            ['name', 'created_at'],
            fn (array $params) => $this->appUserMembershipService->list($params),
        );
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn () => $this->appUserMembershipService->get($id));

        return $this->renderResourceShow(
            'iam/memberships/show',
            lang('Iam.app_user_memberships_details'),
            'appUserMembership',
            $response,
            lang('Iam.app_user_memberships_not_found'),
        );
    }

    public function create(): string
    {
        return $this->render('iam/memberships/create', [
            'title' => lang('Iam.app_user_memberships_create'),
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
            'title' => lang('Iam.app_user_memberships_edit'),
            'item'  => $this->extractData($response),
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
}
