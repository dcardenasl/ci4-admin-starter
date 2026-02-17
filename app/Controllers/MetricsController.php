<?php

namespace App\Controllers;

use App\Services\MetricsApiService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class MetricsController extends BaseWebController
{
    protected MetricsApiService $metricsService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->metricsService = service('metricsApiService');
    }

    public function index(): string
    {
        $dateRange = $this->resolveDateRange();
        $defaultFilters = $this->defaultFilters();
        $groupBy = (string) ($this->request->getGet('group_by') ?: 'day');
        if (! in_array($groupBy, ['day', 'week', 'month'], true)) {
            $groupBy = 'day';
        }

        $filters = $dateRange + ['group_by' => $groupBy];
        $summaryResponse = $this->safeApiCall(fn() => $this->metricsService->summary($filters));
        $timeseriesResponse = $this->safeApiCall(fn() => $this->metricsService->timeseries($filters));

        $metrics = $this->extractData($summaryResponse);
        $timeseries = $this->extractItems($timeseriesResponse);

        if ($timeseries === []) {
            $timeseries = $this->extractData($timeseriesResponse)['timeseries'] ?? [];
        }

        return $this->render('metrics/index', [
            'title'          => lang('Metrics.title'),
            'metrics'        => $metrics,
            'timeseries'     => is_array($timeseries) ? $timeseries : [],
            'filters'        => $filters,
            'defaultFilters' => $defaultFilters,
            'hasFilters'     => has_active_filters($this->request->getGet(), $defaultFilters),
        ]);
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
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'group_by'  => 'day',
        ];
    }
}
