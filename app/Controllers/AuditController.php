<?php

namespace App\Controllers;

use App\Services\AuditApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AuditController extends BaseWebController
{
    protected AuditApiService $auditService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->auditService = service('auditApiService');
    }

    public function index(): string
    {
        return $this->render('audit/index', [
            'title'      => lang('Audit.title'),
        ]);
    }

    public function data(): ResponseInterface
    {
        return $this->tableDataResponse(
            ['action', 'userId', 'entityType', 'entityId'],
            ['createdAt', 'action', 'userId', 'entityType', 'entityId'],
            fn(array $params) => $this->auditService->list($params),
        );
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn() => $this->auditService->get($id));

        return $this->renderResourceShow('audit/show', lang('Audit.details'), 'log', $response, lang('Audit.notFound'));
    }

    public function byEntity(string $type, string $id): RedirectResponse
    {
        $search = rawurlencode(trim($type . ' ' . $id));

        return redirect()->to(site_url('admin/audit?search=' . $search));
    }

}
