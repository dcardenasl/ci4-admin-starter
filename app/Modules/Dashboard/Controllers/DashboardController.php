<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Dashboard\Services\HealthApiServiceInterface;
use App\Modules\Files\Services\FileApiServiceInterface;
use App\Modules\Metrics\Services\MetricsApiServiceInterface;
use App\Modules\Users\Services\UserApiServiceInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class DashboardController extends BaseWebController
{
    protected FileApiServiceInterface $fileService;
    protected HealthApiServiceInterface $healthService;
    protected MetricsApiServiceInterface $metricsService;
    protected UserApiServiceInterface $userService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
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
        $isAdmin = has_permission('users.read');

        // 1. Resource totals from API contract: /users and /files return meta.total
        $usersResponse = $isAdmin
            ? $this->safeApiCall(fn () => $this->userService->list(['limit' => 1]))
            : ['ok' => false, 'data' => []];

        $filesResponse = $this->safeApiCall(fn () => $this->fileService->list(['limit' => 5]));

        // 2. Network metrics from /metrics -> request_stats
        $metricsResponse = $this->safeApiCall(fn () => $this->metricsService->summary($dateRange));

        $healthResponse = $this->safeApiCall(fn () => $this->healthService->check());

        // Data processing
        $metrics = $this->extractData($metricsResponse);
        $health = $healthResponse;

        $totalUsers = 0;
        if ($isAdmin) {
            $payloadUsers = $usersResponse['data'] ?? [];
            $totalUsers = $payloadUsers['meta']['total'] ?? $payloadUsers['data']['meta']['total'] ?? $payloadUsers['total'] ?? 0;
        }

        $payloadFiles = $filesResponse['data'] ?? [];
        $totalFiles = $payloadFiles['meta']['total'] ?? $payloadFiles['data']['meta']['total'] ?? $payloadFiles['total'] ?? 0;
        $recentFiles = $this->extractItems($filesResponse);

        // Build stats from real, available data only
        $stats = [
            'users' => [
                'label' => lang('Dashboard.total_users'),
                'value' => $totalUsers,
                'icon'  => 'users',
            ],
            'files' => [
                'label' => lang('Dashboard.total_files'),
                'value' => $totalFiles,
                'icon'  => 'files',
            ],
        ];

        // Include availability metric only when the API contract provides it
        $uptime = $metrics['request_stats']['availability_percent']
               ?? $metrics['slo']['availability_percent']
               ?? null;

        if ($uptime !== null) {
            $stats['uptime'] = [
                'label' => lang('Dashboard.api_uptime'),
                'value' => $uptime . '%',
                'icon'  => 'activity',
            ];
        }

        return $this->render('dashboard/index', [
            'title' => lang('Dashboard.title'),
            'user'  => session('user') ?? [],
            'stats' => $stats,
            'recentFiles'    => $recentFiles,
            'recent_activity' => $metrics['recent_activity'] ?? [],
            'apiHealth'      => $health,
        ]);
    }
}
