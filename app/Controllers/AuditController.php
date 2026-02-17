<?php

namespace App\Controllers;

use App\Services\AuditApiService;
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
        $filters = array_filter([
            'search'  => (string) $this->request->getGet('search'),
            'action'  => (string) $this->request->getGet('action'),
            'user_id' => (string) $this->request->getGet('user_id'),
            'page'    => (int) ($this->request->getGet('page') ?: 1),
        ]);

        $response = $this->safeApiCall(fn() => $this->auditService->list($filters));

        $data = $response['data'] ?? [];

        return $this->render('audit/index', [
            'title'      => lang('Audit.title'),
            'logs'       => $this->extractItems($response),
            'pagination' => [
                'current_page' => $data['current_page'] ?? 1,
                'last_page'    => $data['last_page'] ?? 1,
                'total'        => $data['total'] ?? 0,
            ],
        ]);
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

    public function byEntity(string $type, string $id): string
    {
        $response = $this->safeApiCall(fn() => $this->auditService->byEntity($type, $id));

        return $this->render('audit/index', [
            'title'      => lang('Audit.entityHistory') . ': ' . esc($type) . ' #' . esc($id),
            'logs'       => $this->extractItems($response),
            'pagination' => [
                'current_page' => 1,
                'last_page'    => 1,
                'total'        => count($this->extractItems($response)),
            ],
        ]);
    }
}
