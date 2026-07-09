<?php

declare(strict_types=1);

namespace Config;

/**
 * Configuration for the HTTP client that talks to a ci4-bff-starter gateway
 * (port 8188 by default). Mirrors {@see DomainApiClient} but reads a distinct
 * set of environment variables so a single admin instance can monitor hub,
 * domain, and BFF health independently.
 *
 * Leave $baseUrl empty (the default) to disable BFF health monitoring.
 */
class BffApiClient extends ApiClient
{
    public string $baseUrl = '';

    public function __construct()
    {
        \CodeIgniter\Config\BaseConfig::__construct();

        $baseUrl = env('bffApiClient.baseUrl') ?: env('BFF_API_BASE_URL');
        if (is_string($baseUrl) && trim($baseUrl) !== '') {
            $this->baseUrl = $baseUrl;
        }

        $timeout = env('bffApiClient.timeout') ?: env('BFF_API_TIMEOUT');
        if ($timeout !== false && $timeout !== null && $timeout !== '') {
            $this->timeout = (int) $timeout;
        }

        $connectTimeout = env('bffApiClient.connectTimeout') ?: env('BFF_API_CONNECT_TIMEOUT');
        if ($connectTimeout !== false && $connectTimeout !== null && $connectTimeout !== '') {
            $this->connectTimeout = (int) $connectTimeout;
        }

        $apiPrefix = env('bffApiClient.apiPrefix') ?: env('BFF_API_PREFIX');
        if (is_string($apiPrefix) && trim($apiPrefix) !== '') {
            $normalizedPrefix = '/' . trim($apiPrefix, '/');
            $this->apiPrefix = $normalizedPrefix === '/' ? '/api/v1' : $normalizedPrefix;
        }

        $appName = env('bffApiClient.appName') ?: env('BFF_API_APP_NAME');
        if (is_string($appName) && trim($appName) !== '') {
            $this->appName = $appName;
        }

        $appKey = env('bffApiClient.appKey') ?: env('BFF_API_APP_KEY');
        if (is_string($appKey) && trim($appKey) !== '') {
            $this->appKey = $appKey;
        }

        $val = env('bffApiClient.healthPaths') ?: env('BFF_API_HEALTH_PATHS');
        if ($val) {
            $paths = array_values(array_filter(array_map('trim', explode(',', (string) $val))));
            if ($paths !== []) {
                $this->healthPaths = $paths;
            }
        }

        $logRequests = env('bffApiClient.logRequests') ?: env('BFF_API_LOG_REQUESTS');
        if ($logRequests !== null && $logRequests !== '') {
            $this->logRequests = filter_var($logRequests, FILTER_VALIDATE_BOOLEAN);
        }
    }
}
