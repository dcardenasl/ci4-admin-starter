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
        $isAdmin   = has_permission('users.read');
        $userId    = (int) ((session('user') ?? [])['id'] ?? 0);
        $cache     = service('cache');

        // Health (30s — real-time status; errors are not cached so the next load retries)
        $healthResponse = $cache->get('dashboard_health');
        if (!is_array($healthResponse)) {
            $healthResponse = $this->safeApiCall(fn () => $this->healthService->check());
            if ($healthResponse['ok'] ?? false) {
                $cache->save('dashboard_health', $healthResponse, 30);
            }
        }

        // Files: user-scoped (60s — changes on upload/delete, but brief staleness is acceptable)
        $filesCacheKey = 'dashboard_files_' . $userId;
        $filesResponse = $cache->get($filesCacheKey);
        if (!is_array($filesResponse)) {
            $filesResponse = $this->safeApiCall(fn () => $this->fileService->list(['limit' => 5]));
            if ($filesResponse['ok'] ?? false) {
                $cache->save($filesCacheKey, $filesResponse, 60);
            }
        }

        // Metrics: keyed on date range (120s — historical stats, infrequent changes)
        $metricsCacheKey = 'dashboard_metrics_' . md5(serialize($dateRange));
        $metricsResponse = $cache->get($metricsCacheKey);
        if (!is_array($metricsResponse)) {
            $metricsResponse = $this->safeApiCall(fn () => $this->metricsService->summary($dateRange));
            if ($metricsResponse['ok'] ?? false) {
                $cache->save($metricsCacheKey, $metricsResponse, 120);
            }
        }

        // Users total: global count (120s — changes infrequently)
        if ($isAdmin) {
            $usersResponse = $cache->get('dashboard_users');
            if (!is_array($usersResponse)) {
                $usersResponse = $this->safeApiCall(fn () => $this->userService->list(['limit' => 1]));
                if ($usersResponse['ok'] ?? false) {
                    $cache->save('dashboard_users', $usersResponse, 120);
                }
            }
        } else {
            $usersResponse = ['ok' => false, 'data' => []];
        }

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
