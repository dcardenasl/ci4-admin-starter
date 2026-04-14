<?php

declare(strict_types=1);

namespace AppModulesAuditControllers;
use App\Controllers\BaseWebController;

use App\Services\CatalogApiService;
use AppModulesAuditServicesAuditApiService;
use App\Support\CatalogOptions;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AuditController extends BaseWebController
{
    protected AuditApiService $auditService;
    protected CatalogApiService $catalogService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->auditService = service('auditApiService');
        $this->catalogService = service('catalogApiService');
    }

    public function index(): string
    {
        $catalogs = $this->resolveCatalogs($this->catalogService);

        $facetsResponse = $this->safeApiCall(fn() => $this->catalogService->auditFacets());
        $facets = $this->extractData($facetsResponse);
        if (! is_array($facets)) {
            $facets = [];
        }

        $actionOptions = CatalogOptions::options(
            ['audit' => ['actions' => $facets['actions'] ?? []]],
            'audit.actions',
            [
                ['value' => 'create', 'label' => lang('Audit.action_create')],
                ['value' => 'update', 'label' => lang('Audit.action_update')],
                ['value' => 'delete', 'label' => lang('Audit.action_delete')],
                ['value' => 'login', 'label' => lang('Audit.action_login')],
                ['value' => 'login_success', 'label' => lang('Audit.action_login_success')],
                ['value' => 'login_failure', 'label' => lang('Audit.action_login_failure')],
                ['value' => 'logout', 'label' => lang('Audit.action_logout')],
                ['value' => 'approve', 'label' => lang('Audit.action_approve')],
            ]
        );

        return $this->render('audit/index', [
            'title'         => lang('Audit.title'),
            'actionOptions' => $actionOptions,
            'limitOptions'  => CatalogOptions::limitOptions($catalogs),
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            ['action', 'user_id', 'entity_type', 'entity_id', 'result', 'severity'],
            ['created_at', 'action', 'user_id', 'entity_type', 'entity_id', 'ip_address', 'user_agent', 'result', 'severity'],
            fn(array $params) => $this->auditService->list($params),
        );
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn() => $this->auditService->get($id));

        return $this->renderResourceShow('audit/show', lang('Audit.details'), 'log', $response, lang('Audit.not_found'));
    }

    public function byEntity(string $type, string $id): RedirectResponse
    {
        $search = rawurlencode(trim($type . ' ' . $id));

        return redirect()->to(route_to('admin.audit') . '?search=' . $search);
    }

}
