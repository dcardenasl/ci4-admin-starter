<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ApiClient extends BaseConfig
{
    public string $baseUrl = 'http://localhost:8080';

    public int $timeout = 15;

    public int $connectTimeout = 5;

    public string $apiPrefix = '/api/v1';
}
