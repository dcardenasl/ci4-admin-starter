<?php

declare(strict_types=1);

namespace Config;

/**
 * Configuration for the secondary HTTP client that talks to a domain-starter
 * app (port 8190 by default). Mirrors {@see ApiClient} but reads a distinct
 * set of environment variables so a single admin instance can drive both the
 * hub (ApiClient) and one domain backend (DomainApiClient) in parallel.
 */
class DomainApiClient extends ApiClient
{
    public string $baseUrl = '';

    public function __construct()
    {
        // Skip ApiClient::__construct — it reads `apiClient.*` / `API_*` keys
        // that belong to the hub. We re-implement env reading against the
        // `domainApiClient.*` / `DOMAIN_API_*` namespace instead.
        \CodeIgniter\Config\BaseConfig::__construct();

        $baseUrl = env('domainApiClient.baseUrl') ?: env('DOMAIN_API_BASE_URL');
        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new \LogicException(
                lang('Config.missingDomainApiBaseUrl') ?? (
                    'Missing DOMAIN_API_BASE_URL in .env. '
                    . 'Set domainApiClient.baseUrl or DOMAIN_API_BASE_URL to your domain API server URL. '
                    . 'Example: DOMAIN_API_BASE_URL=http://localhost:8190'
                )
            );
        }
        $this->baseUrl = $baseUrl;

        $timeout = env('domainApiClient.timeout') ?: env('DOMAIN_API_TIMEOUT');
        if ($timeout !== false && $timeout !== null && $timeout !== '') {
            $this->timeout = (int) $timeout;
        }

        $connectTimeout = env('domainApiClient.connectTimeout') ?: env('DOMAIN_API_CONNECT_TIMEOUT');
        if ($connectTimeout !== false && $connectTimeout !== null && $connectTimeout !== '') {
            $this->connectTimeout = (int) $connectTimeout;
        }

        $apiPrefix = env('domainApiClient.apiPrefix') ?: env('DOMAIN_API_PREFIX');
        if (is_string($apiPrefix) && trim($apiPrefix) !== '') {
            $normalizedPrefix = '/' . trim($apiPrefix, '/');
            $this->apiPrefix = $normalizedPrefix === '/' ? '/api/v1' : $normalizedPrefix;
        }

        $appName = env('domainApiClient.appName') ?: env('DOMAIN_API_APP_NAME');
        if (is_string($appName) && trim($appName) !== '') {
            $this->appName = $appName;
        }

        $appKey = env('domainApiClient.appKey') ?: env('DOMAIN_API_APP_KEY');
        if (is_string($appKey) && trim($appKey) !== '') {
            $this->appKey = $appKey;
        }

        $val = env('domainApiClient.healthPaths') ?: env('DOMAIN_API_HEALTH_PATHS');
        if ($val) {
            $paths = array_values(array_filter(array_map('trim', explode(',', (string) $val))));
            if ($paths !== []) {
                $this->healthPaths = $paths;
            }
        }

        $logRequests = env('domainApiClient.logRequests') ?: env('DOMAIN_API_LOG_REQUESTS');
        if ($logRequests !== null && $logRequests !== '') {
            $this->logRequests = filter_var($logRequests, FILTER_VALIDATE_BOOLEAN);
        }
    }
}
