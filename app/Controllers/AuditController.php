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
        $tableState = $this->resolveTableState(
            ['action', 'user_id'],
            ['created_at', 'action', 'user_id', 'entity_type'],
        );
        $response = $this->safeApiCall(fn() => $this->auditService->list($this->buildTableApiParams($tableState)));

        return $this->passthroughApiJsonResponse($response);
    }

    public function show(string $id): string
    {
        $response = $this->safeApiCall(fn() => $this->auditService->get($id));

        if (! $response['ok']) {
            return $this->render('audit/show', [
                'title' => lang('Audit.details'),
                'log'   => [],
                'error' => $this->firstMessage($response, lang('Audit.notFound')),
            ]);
        }

        return $this->render('audit/show', [
            'title' => lang('Audit.details'),
            'log'   => $this->extractData($response),
        ]);
    }

    public function byEntity(string $type, string $id): RedirectResponse
    {
        $search = rawurlencode(trim($type . ' ' . $id));

        return redirect()->to(site_url('admin/audit?search=' . $search));
    }

}
