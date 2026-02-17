<?php

namespace App\Controllers;

use App\Services\ReportApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ReportController extends BaseWebController
{
    protected ReportApiService $reportService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->reportService = service('reportApiService');
    }

    public function index(): string
    {
        $filters = $this->collectFilters();
        $defaultFilters = $this->defaultFilters();

        return $this->render('reports/index', [
            'title'          => lang('Reports.title'),
            'filters'        => $filters,
            'defaultFilters' => $defaultFilters,
        ]);
    }

    public function data(): ResponseInterface
    {
        $filters = $this->collectFilters();
        $response = $this->safeApiCall(fn() => $this->reportService->list($this->buildListParams($filters)));

        return $this->passthroughApiJsonResponse($response);
    }

    public function exportCsv(): ResponseInterface|RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->reportService->exportCsv($this->buildListParams($this->collectFilters(), true)));

        return $this->buildExportResponse($response, 'csv');
    }

    public function exportPdf(): ResponseInterface|RedirectResponse
    {
        $response = $this->safeApiCall(fn() => $this->reportService->exportPdf($this->buildListParams($this->collectFilters(), true)));

        return $this->buildExportResponse($response, 'pdf');
    }

    private function collectFilters(): array
    {
        $tableState = $this->resolveTableState(
            ['status', 'role', 'action'],
            ['created_at', 'email', 'role', 'status', 'action'],
        );

        $dateRange = $this->resolveDateRange();
        $reportType = (string) ($this->request->getGet('report_type') ?: 'users');
        if (! in_array($reportType, ['users', 'activity', 'files'], true)) {
            $reportType = 'users';
        }

        $groupBy = (string) ($this->request->getGet('group_by') ?: 'day');
        if (! in_array($groupBy, ['day', 'week', 'month'], true)) {
            $groupBy = 'day';
        }

        return $dateRange + [
            'report_type' => $reportType,
            'group_by'    => $groupBy,
            'table'       => $tableState,
            'search'      => $tableState['search'],
            'status'      => $tableState['filters']['status'] ?? '',
            'role'        => $tableState['filters']['role'] ?? '',
            'action'      => $tableState['filters']['action'] ?? '',
            'sort'        => $tableState['sort'],
            'limit'       => $tableState['limit'],
            'cursor'      => $tableState['cursor'],
            'page'        => $tableState['page'],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function buildListParams(array $filters, bool $forExport = false): array
    {
        $table = $filters['table'] ?? [];
        if (! is_array($table)) {
            $table = [];
        }

        $params = $this->buildTableApiParams($table, [
            'date_from'   => (string) ($filters['date_from'] ?? ''),
            'date_to'     => (string) ($filters['date_to'] ?? ''),
            'report_type' => (string) ($filters['report_type'] ?? 'users'),
            'group_by'    => (string) ($filters['group_by'] ?? 'day'),
        ]);

        if ($forExport) {
            unset($params['cursor'], $params['page'], $params['limit']);
        }

        return $params;
    }

    private function buildExportResponse(array $apiResponse, string $format): ResponseInterface|RedirectResponse
    {
        if (! ($apiResponse['ok'] ?? false)) {
            return redirect()->back()->with('error', $this->firstMessage($apiResponse, lang('Reports.exportFailed')));
        }

        $payload = $this->extractData($apiResponse);

        if (isset($payload['download_url']) && is_string($payload['download_url']) && $payload['download_url'] !== '') {
            return redirect()->to($payload['download_url']);
        }

        $filename = (string) ($payload['filename'] ?? ('report-' . date('Ymd-His') . '.' . $format));

        if (isset($payload['content']) && is_string($payload['content']) && $payload['content'] !== '') {
            $content = ! empty($payload['is_base64']) ? base64_decode($payload['content'], true) : $payload['content'];

            if (is_string($content) && $content !== '') {
                return $this->response->download($filename, $content);
            }
        }

        if (($apiResponse['raw'] ?? '') !== '' && ($apiResponse['data'] ?? []) === []) {
            return $this->response->download($filename, (string) $apiResponse['raw']);
        }

        return redirect()->back()->with('success', lang('Reports.exportReady'));
    }

    /**
     * @return array<string, string>
     */
    private function defaultFilters(): array
    {
        $today = new \DateTimeImmutable('today');
        $dateTo = $today->format('Y-m-d');
        $dateFrom = $today->sub(new \DateInterval('P29D'))->format('Y-m-d');

        return [
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'report_type' => 'users',
            'group_by'    => 'day',
            'limit'       => '25',
            'search'      => '',
            'status'      => '',
            'role'        => '',
            'action'      => '',
        ];
    }
}
