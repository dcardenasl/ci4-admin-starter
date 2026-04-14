<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Controllers;

use App\Controllers\BaseWebController;
use App\Services\CatalogApiService;
use App\Modules\Metrics\Services\MetricsApiServiceInterface;
use App\Support\CatalogOptions;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class MetricsController extends BaseWebController
{
    protected MetricsApiServiceInterface $metricsService;
    protected CatalogApiService $catalogService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->metricsService = service('metricsApiService');
        $this->catalogService = service('catalogApiService');
    }

    public function index(): string
    {
        $catalogs = $this->resolveCatalogs($this->catalogService);

        $periodOptions = CatalogOptions::options($catalogs, 'metrics.periods', [
            ['value' => '1h', 'label' => '1h'],
            ['value' => '24h', 'label' => '24h'],
            ['value' => '7d', 'label' => '7d'],
            ['value' => '30d', 'label' => '30d'],
        ]);

        $defaultFilters = $this->defaultFilters();
        $period = trim((string) ($this->request->getGet('period') ?? '24h'));
        $allowedPeriods = array_column($periodOptions, 'value');
        if (! in_array($period, $allowedPeriods, true)) {
            $period = '24h';
        }

        $viewFilters = ['period' => $period];

        $apiParams = [
            'period' => $period,
        ];

        $summaryResponse = $this->safeApiCall(fn() => $this->metricsService->summary($apiParams));
        $timeseriesResponse = $this->safeApiCall(fn() => $this->metricsService->timeseries($apiParams));

        $summaryData = $this->extractData($summaryResponse);
        $timeseriesData = $this->extractData($timeseriesResponse);

        // If timeseries is empty in the items extraction, look for it in the data payload
        $timeseries = $this->extractItems($timeseriesResponse);
        if ($timeseries === [] || (is_array($timeseries) && ! isset($timeseries[0]))) {
            $timeseries = $timeseriesData['timeseries'] ?? $timeseriesData['data'] ?? $timeseriesData['items'] ?? $timeseriesData;
        }

        // Transform parallel arrays (dates, requests, etc.) to a list of objects for the table
        if (is_array($timeseries) && isset($timeseries['dates']) && is_array($timeseries['dates'])) {
            $points = [];
            foreach ($timeseries['dates'] as $i => $date) {
                $points[] = [
                    'period' => $date,
                    'value' => $timeseries['requests'][$i] ?? 0,
                    'errors' => $timeseries['errors'][$i] ?? 0,
                    'latency' => $timeseries['latency'][$i] ?? 0,
                ];
            }
            $timeseries = $points;
        }

        return $this->render('metrics/index', [
            'title'          => lang('Metrics.title'),
            'metrics'        => $summaryData,
            'timeseries'     => is_array($timeseries) ? $timeseries : [],
            'filters'        => $viewFilters,
            'defaultFilters' => $defaultFilters,
            'hasFilters'     => has_active_filters($this->request->getGet(), $defaultFilters),
            'periodOptions'  => $periodOptions,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function defaultFilters(): array
    {
        return [
            'period' => '24h',
        ];
    }
}
