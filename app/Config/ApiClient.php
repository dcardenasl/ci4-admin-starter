<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ApiClient extends BaseConfig
{
    public string $baseUrl = 'http://localhost:8080';

    public int $timeout = 15;

    public int $connectTimeout = 5;

    public string $apiPrefix = '/api/v1';

    public string $appName = 'API Client';

    public string $appKey = '';

    /**
     * @var list<string>
     */
    public array $healthPaths = ['/health'];

    public bool $logRequests = false;

    public function __construct()
    {
        parent::__construct();

        // Support both CodeIgniter dotted .env keys (apiClient.*)
        // and uppercase env vars (API_*) for compatibility across deployments.
        $baseUrl = env('apiClient.baseUrl') ?: env('API_BASE_URL');
        if (is_string($baseUrl) && trim($baseUrl) !== '') {
            $this->baseUrl = $baseUrl;
        }

        $timeout = env('apiClient.timeout') ?: env('API_TIMEOUT');
        if ($timeout !== false && $timeout !== null && $timeout !== '') {
            $this->timeout = (int) $timeout;
        }

        $connectTimeout = env('apiClient.connectTimeout') ?: env('API_CONNECT_TIMEOUT');
        if ($connectTimeout !== false && $connectTimeout !== null && $connectTimeout !== '') {
            $this->connectTimeout = (int) $connectTimeout;
        }

        $appName = env('apiClient.appName') ?: env('APP_NAME');
        if (is_string($appName) && trim($appName) !== '') {
            $this->appName = $appName;
        }

        $appKey = env('apiClient.appKey') ?: env('API_APP_KEY');
        if (is_string($appKey) && trim($appKey) !== '') {
            $this->appKey = $appKey;
        }

        $val = env('apiClient.healthPaths') ?: env('API_HEALTH_PATHS');
        if ($val) {
            $paths = array_values(array_filter(array_map('trim', explode(',', (string) $val))));
            if ($paths !== []) {
                $this->healthPaths = $paths;
            }
        }

        $logRequests = env('apiClient.logRequests') ?: env('API_LOG_REQUESTS');
        if ($logRequests !== null && $logRequests !== '') {
            $this->logRequests = filter_var($logRequests, FILTER_VALIDATE_BOOLEAN);
        }
    }
}
