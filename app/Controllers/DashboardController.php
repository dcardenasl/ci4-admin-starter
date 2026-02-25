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
            
        $filesResponse = $this->safeApiCall(fn() => $this->fileService->list(['limit' => 5]));
        
        // 2. Métricas de red (según contrato /metrics -> request_stats)
        $metricsResponse = $this->safeApiCall(fn() => $this->metricsService->summary($dateRange));
        $healthResponse = $this->safeApiCall(fn() => $this->healthService->check());

        // Procesamiento de datos
        $metrics = $this->extractData($metricsResponse);
        
        $totalUsers = 0;
        if (has_admin_access((string) (session('user.role') ?? ''))) {
            $totalUsers = $usersResponse['data']['meta']['total'] ?? 0;
        }
        
        $totalFiles = $filesResponse['data']['meta']['total'] ?? 0;
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

        // Añadir métricas de red solo si el contrato las provee
        if (isset($metrics['request_stats']['availability_percent'])) {
            $stats['uptime'] = [
                'label' => lang('Dashboard.apiUptime'),
                'value' => $metrics['request_stats']['availability_percent'] . '%',
                'icon'  => 'activity',
            ];
        }

        return $this->render('dashboard/index', [
            'title' => lang('Dashboard.title'),
            'user'  => session('user') ?? [],
            'stats' => $stats,
            'recentFiles'    => $recentFiles,
            'recentActivity' => $metrics['recent_activity'] ?? [],
            'apiHealth'      => $healthResponse,
        ]);
    }
}
