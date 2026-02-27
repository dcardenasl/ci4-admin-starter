<?php

namespace App\Controllers;

use Config\Services;

class DebugController extends BaseWebController
{
    public function index()
    {
        $session = session()->get();
        $apiClient = service('apiClient');

        $testCall = $apiClient->get('/health');
        $usersCall = $apiClient->get('/users', ['limit' => 1]);

        return $this->response->setJSON([
            'session' => [
                'has_access_token' => session()->has('access_token'),
                'user' => $session['user'] ?? null,
                'token_expires_at' => $session['token_expires_at'] ?? null,
                'now' => time(),
            ],
            'api_health' => [
                'status' => $testCall['status'],
                'ok' => $testCall['ok'],
                'data' => $testCall['data'],
            ],
            'api_users' => [
                'status' => $usersCall['status'],
                'ok' => $usersCall['ok'],
                'messages' => $usersCall['messages'],
            ],
        ]);
    }
}
