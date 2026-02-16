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
        $fileResponse = $this->fileService->list(['per_page' => 5]);
        $files = $this->extractItems($fileResponse);
        $user = session('user') ?? [];

        return $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => [
                'files'         => count($files),
                'role'          => $user['role'] ?? 'user',
                'emailVerified' => ! empty($user['email_verified_at']) ? 'Si' : 'No',
            ],
            'recentFiles' => $files,
        ]);
    }

    protected function extractItems(array $response): array
    {
        $data = $response['data'] ?? [];

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }
}
