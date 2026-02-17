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
        $response = $this->safeApiCall(fn() => $this->metricsService->get());

        return $this->render('metrics/index', [
            'title'   => lang('Metrics.title'),
            'metrics' => $this->extractData($response),
        ]);
    }
}
