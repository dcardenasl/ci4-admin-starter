<?php

namespace App\Controllers;

use App\Services\FileApiService;
use App\Services\HealthApiService;
use App\Services\MetricsApiService;
use App\Services\UserApiService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class DashboardController extends BaseWebController
{
    protected FileApiService $fileService;
    protected HealthApiService $healthService;
    protected MetricsApiService $metricsService;
    protected UserApiService $userService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->fileService = service('fileApiService');
        $this->healthService = service('healthApiService');
        $this->metricsService = service('metricsApiService');
        $this->userService = service('userApiService');
    }

    public function index(): string
    {
        $dateRange = $this->resolveDateRange();
        $isAdmin = has_admin_access((string) (session('user.role') ?? ''));

        // 1. Recursos con sus totales reales (según contrato /users y /files -> meta.total)
        $usersResponse = $isAdmin
            ? $this->safeApiCall(fn() => $this->userService->list(['limit' => 1]))
            : ['ok' => false, 'data' => []];

        log_message('debug', 'Dashboard Users Response: ' . ($usersResponse['ok'] ? 'OK' : 'FAIL'));

        $filesResponse = $this->safeApiCall(fn() => $this->fileService->list(['limit' => 5]));
        log_message('debug', 'Dashboard Files Response: ' . ($filesResponse['ok'] ? 'OK' : 'FAIL'));

        // 2. Métricas de red (según contrato /metrics -> request_stats)
        $metricsResponse = $this->safeApiCall(fn() => $this->metricsService->summary($dateRange));
        log_message('debug', 'Dashboard Metrics Response: ' . ($metricsResponse['ok'] ? 'OK' : 'FAIL'));

        $healthResponse = $this->safeApiCall(fn() => $this->healthService->check());
        log_message('debug', 'Dashboard Health Response: ' . ($healthResponse['ok'] ? 'OK' : 'FAIL'));

        // Procesamiento de datos
        $metrics = $this->extractData($metricsResponse);

        $totalUsers = 0;
        if (has_admin_access((string) (session('user.role') ?? ''))) {
            $payloadUsers = $usersResponse['data'] ?? [];
            $totalUsers = $payloadUsers['meta']['total'] ?? $payloadUsers['data']['meta']['total'] ?? $payloadUsers['total'] ?? 0;
        }

        $payloadFiles = $filesResponse['data'] ?? [];
        $totalFiles = $payloadFiles['meta']['total'] ?? $payloadFiles['data']['meta']['total'] ?? $payloadFiles['total'] ?? 0;
        $recentFiles = $this->extractItems($filesResponse);

        // Definición de estadísticas basadas en información REAL y EXISTENTE
        $stats = [
            'users' => [
                'label' => lang('Dashboard.totalUsers'),
                'value' => $totalUsers,
                'icon'  => 'users',
            ],
            'files' => [
                'label' => lang('Dashboard.totalFiles'),
                'value' => $totalFiles,
                'icon'  => 'files',
            ],
        ];

        // Añadir métricas de red solo si el contrato o la respuesta las provee (con fallbacks robustos)
        $uptime = $metrics['request_stats']['availability_percent']
               ?? $metrics['slo']['availability']['current']
               ?? $metrics['availability_percent']
               ?? $metrics['uptime_percent']
               ?? null;

        if ($uptime !== null) {
            $stats['uptime'] = [
                'label' => lang('Dashboard.apiUptime'),
                'value' => $uptime . '%',
                'icon'  => 'activity',
            ];
        }

        return $this->render('dashboard/index', [
            'title' => lang('Dashboard.title'),
            'user'  => session('user') ?? [],
            'stats' => $stats,
            'recentFiles'    => $recentFiles,
            'recentActivity' => $metrics['recentActivity'] ?? $metrics['recent_activity'] ?? [],
            'apiHealth'      => $healthResponse,
        ]);
    }
}
