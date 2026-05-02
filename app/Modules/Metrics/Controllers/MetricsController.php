<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Metrics\Services\MetricsApiServiceInterface;
use App\Support\CatalogOptions;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class MetricsController extends BaseWebController
{
    protected MetricsApiServiceInterface $metricsService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->metricsService = service('metricsApiService');
    }

    public function index(): string
    {
        $periodOptions = CatalogOptions::options([], 'metrics.periods', [
            ['value' => '1h', 'label' => '1h'],
            ['value' => '24h', 'label' => '24h'],
            ['value' => '7d', 'label' => '7d'],
            ['value' => '30d', 'label' => '30d'],
        ]);

        $defaultFilters = $this->defaultFilters();
        $rawPeriod = $this->request->getGet('period');
        $period = trim(is_string($rawPeriod) ? $rawPeriod : '24h');
        $allowedPeriods = array_column($periodOptions, 'value');
        if (! in_array($period, $allowedPeriods, true)) {
            $period = '24h';
        }

        $viewFilters = ['period' => $period];

        $apiParams = [
            'period' => $period,
        ];

        $summaryResponse = $this->safeApiCall(fn () => $this->metricsService->summary($apiParams));
        $timeseriesResponse = $this->safeApiCall(fn () => $this->metricsService->timeseries($apiParams));

        $summaryData = $this->extractData($summaryResponse);
        $timeseriesData = $this->extractData($timeseriesResponse);

        return $this->render('metrics/index', [
            'title'          => lang('Metrics.title'),
            'metrics'        => $summaryData,
            'timeseries'     => $timeseriesData,
            'filters'        => $viewFilters,
            'defaultFilters' => $defaultFilters,
            'hasFilters'     => has_active_filters(is_array($this->request->getGet()) ? $this->request->getGet() : null, $defaultFilters),
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
