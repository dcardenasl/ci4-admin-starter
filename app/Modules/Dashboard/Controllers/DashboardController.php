<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Dashboard\Services\HealthApiServiceInterface;
use App\Modules\Files\Services\FileApiServiceInterface;
use App\Modules\Metrics\Services\MetricsApiServiceInterface;
use App\Modules\Users\Services\UserApiServiceInterface;
use CodeIgniter\Cache\CacheInterface;
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
        $this->fileService    = service('fileApiService');
        $this->healthService  = service('healthApiService');
        $this->metricsService = service('metricsApiService');
        $this->userService    = service('userApiService');
    }

    public function index(): string
    {
        return $this->render('dashboard/index', [
            'title' => lang('Dashboard.title'),
            'user'  => session('user') ?? [],
        ]);
    }

    public function widgetStats(): ResponseInterface
    {
        $isAdmin   = has_permission('users.read');
        $userId    = (int) ((session('user') ?? [])['id'] ?? 0);
        $cache     = service('cache');
        $dateRange = $this->resolveDateRange();

        $metricsCacheKey = 'dashboard_metrics_' . md5(serialize($dateRange));
        $metricsResponse = $cache->get($metricsCacheKey);
        if (!is_array($metricsResponse)) {
            $metricsResponse = $this->safeApiCall(fn () => $this->metricsService->summary($dateRange));
            if ($metricsResponse['ok'] ?? false) {
                $cache->save($metricsCacheKey, $metricsResponse, 120);
            }
        }
        $metrics = $this->extractData($metricsResponse);

        $filesCacheKey = 'dashboard_files_' . $userId;
        $filesResponse = $cache->get($filesCacheKey);
        if (!is_array($filesResponse)) {
            $filesResponse = $this->safeApiCall(fn () => $this->fileService->list(['limit' => 5]));
            if ($filesResponse['ok'] ?? false) {
                $cache->save($filesCacheKey, $filesResponse, 60);
            }
        }
        $payloadFiles = $filesResponse['data'] ?? [];
        $totalFiles   = $payloadFiles['meta']['total'] ?? $payloadFiles['data']['meta']['total'] ?? $payloadFiles['total'] ?? 0;

        $usersResponse = ['ok' => false, 'data' => []];
        if ($isAdmin) {
            $usersResponse = $cache->get('dashboard_users');
            if (!is_array($usersResponse)) {
                $usersResponse = $this->safeApiCall(fn () => $this->userService->list(['limit' => 1]));
                if ($usersResponse['ok'] ?? false) {
                    $cache->save('dashboard_users', $usersResponse, 120);
                }
            }
        }
        $payloadUsers = $usersResponse['data'] ?? [];
        $totalUsers   = $isAdmin
            ? ($payloadUsers['meta']['total'] ?? $payloadUsers['data']['meta']['total'] ?? $payloadUsers['total'] ?? 0)
            : 0;

        $uptime = $metrics['request_stats']['availability_percent']
               ?? $metrics['slo']['availability_percent']
               ?? null;

        $stats = [
            'users' => ['label' => lang('Dashboard.total_users'), 'value' => $totalUsers, 'icon' => 'users'],
            'files' => ['label' => lang('Dashboard.total_files'), 'value' => $totalFiles, 'icon' => 'files'],
        ];
        if ($uptime !== null) {
            $stats['uptime'] = ['label' => lang('Dashboard.api_uptime'), 'value' => $uptime . '%', 'icon' => 'activity'];
        }

        return $this->response->setBody(view('dashboard/partials/widget_stats', ['stats' => $stats]));
    }

    public function widgetHealth(): ResponseInterface
    {
        $cache = service('cache');

        $hubHealth = $this->fetchCachedHealth('dashboard_health_hub', $this->healthService, $cache);

        $domainUrl    = config('DomainApiClient')->baseUrl;
        $domainHealth = ($domainUrl !== '')
            ? $this->fetchCachedHealth('dashboard_health_domain', service('domainHealthApiService'), $cache)
            : null;

        $bffUrl    = config('BffApiClient')->baseUrl;
        $bffHealth = ($bffUrl !== '')
            ? $this->fetchCachedHealth('dashboard_health_bff', service('bffHealthApiService'), $cache)
            : null;

        $healthServices = [
            ['name' => lang('Dashboard.service_hub'), 'health' => $hubHealth],
        ];
        if ($domainHealth !== null) {
            $healthServices[] = ['name' => lang('Dashboard.service_domain'), 'health' => $domainHealth];
        }
        if ($bffHealth !== null) {
            $healthServices[] = ['name' => lang('Dashboard.service_bff'), 'health' => $bffHealth];
        }

        return $this->response->setBody(view('dashboard/partials/widget_health', [
            'healthServices' => $healthServices,
        ]));
    }

    public function widgetRecentFiles(): ResponseInterface
    {
        $userId        = (int) ((session('user') ?? [])['id'] ?? 0);
        $cache         = service('cache');
        $filesCacheKey = 'dashboard_files_' . $userId;

        $filesResponse = $cache->get($filesCacheKey);
        if (!is_array($filesResponse)) {
            $filesResponse = $this->safeApiCall(fn () => $this->fileService->list(['limit' => 5]));
            if ($filesResponse['ok'] ?? false) {
                $cache->save($filesCacheKey, $filesResponse, 60);
            }
        }

        return $this->response->setBody(view('dashboard/partials/widget_recent_files', [
            'recentFiles' => $this->extractItems($filesResponse),
        ]));
    }

    public function widgetActivity(): ResponseInterface
    {
        $cache           = service('cache');
        $dateRange       = $this->resolveDateRange();
        $metricsCacheKey = 'dashboard_metrics_' . md5(serialize($dateRange));

        $metricsResponse = $cache->get($metricsCacheKey);
        if (!is_array($metricsResponse)) {
            $metricsResponse = $this->safeApiCall(fn () => $this->metricsService->summary($dateRange));
            if ($metricsResponse['ok'] ?? false) {
                $cache->save($metricsCacheKey, $metricsResponse, 120);
            }
        }
        $metrics = $this->extractData($metricsResponse);

        return $this->response->setBody(view('dashboard/partials/widget_activity', [
            'recent_activity' => $metrics['recent_activity'] ?? [],
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCachedHealth(string $cacheKey, HealthApiServiceInterface $service, CacheInterface $cache): array
    {
        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->safeApiCall(fn () => $service->check());
        if ($response['ok'] ?? false) {
            $cache->save($cacheKey, $response, 30);
        }

        return $response;
    }
}
