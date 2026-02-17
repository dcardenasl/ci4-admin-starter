<?php

namespace App\Controllers;

use App\Services\FileApiService;
use App\Services\HealthApiService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class DashboardController extends BaseWebController
{
    protected FileApiService $fileService;
    protected HealthApiService $healthService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->fileService = service('fileApiService');
        $this->healthService = service('healthApiService');
    }

    public function index(): string
    {
        $fileResponse = $this->safeApiCall(fn() => $this->fileService->list(['limit' => 5]));
        $files = $this->extractItems($fileResponse);
        $health = $this->safeApiCall(fn() => $this->healthService->check());
        $user = session('user') ?? [];

        return $this->render('dashboard/index', [
            'title' => lang('Dashboard.title'),
            'stats' => [
                'files'         => count($files),
                'role'          => $user['role'] ?? 'user',
                'emailVerified' => ! empty($user['email_verified_at']) ? lang('App.yes') : lang('App.no'),
                'apiHealth'     => match ($health['state'] ?? 'down') {
                    'up'       => lang('Dashboard.up'),
                    'degraded' => lang('Dashboard.degraded'),
                    default    => lang('Dashboard.down'),
                },
            ],
            'recentFiles' => $files,
            'apiHealth'   => $health,
        ]);
    }
}
