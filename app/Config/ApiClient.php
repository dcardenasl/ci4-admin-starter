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

    /**
     * @var list<string>
     */
    public array $healthPaths = ['/health'];

    public function __construct()
    {
        parent::__construct();

        if ($val = env('API_BASE_URL')) {
            $this->baseUrl = $val;
        }
        if ($val = env('API_TIMEOUT')) {
            $this->timeout = (int) $val;
        }
        if ($val = env('API_CONNECT_TIMEOUT')) {
            $this->connectTimeout = (int) $val;
        }
        if ($val = env('APP_NAME')) {
            $this->appName = $val;
        }
        if ($val = env('API_HEALTH_PATHS')) {
            $paths = array_values(array_filter(array_map('trim', explode(',', (string) $val))));
            if ($paths !== []) {
                $this->healthPaths = $paths;
            }
        }
    }
}
