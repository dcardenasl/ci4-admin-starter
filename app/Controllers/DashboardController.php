<?php

namespace App\Controllers;

use App\Services\FileApiService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class DashboardController extends BaseWebController
{
    protected FileApiService $fileService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->fileService = service('fileApiService');
    }

    public function index(): string
    {
        $fileResponse = $this->safeApiCall(fn () => $this->fileService->list(['per_page' => 5]));
        $files = $this->extractItems($fileResponse);
        $user = session('user') ?? [];

        return $this->render('dashboard/index', [
            'title' => lang('Dashboard.title'),
            'stats' => [
                'files'         => count($files),
                'role'          => $user['role'] ?? 'user',
                'emailVerified' => ! empty($user['email_verified_at']) ? lang('App.yes') : lang('App.no'),
            ],
            'recentFiles' => $files,
        ]);
    }
}
