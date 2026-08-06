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
    protected string $configPrefix = 'domainApiClient';

    protected string $envPrefix = 'DOMAIN_API';

    protected string $backendLabel = 'domain API';

    protected string $exampleBaseUrl = 'http://localhost:8190';

    public string $baseUrl = '';

    public function __construct()
    {
        // Skip ApiClient::__construct — it reads `apiClient.*` / `API_*` keys
        // that belong to the hub. We re-implement env reading against the
        // `domainApiClient.*` / `DOMAIN_API_*` namespace instead.
        \CodeIgniter\Config\BaseConfig::__construct();

        $baseUrl = env($this->configPrefix . '.baseUrl') ?: env($this->envPrefix . '_BASE_URL');
        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new \LogicException(
                lang('Config.missingDomainApiBaseUrl') ?? (
                    'Missing ' . $this->envPrefix . '_BASE_URL in .env. '
                    . 'Set ' . $this->configPrefix . '.baseUrl or ' . $this->envPrefix . '_BASE_URL to your '
                    . $this->backendLabel . ' server URL. '
                    . 'Example: ' . $this->envPrefix . '_BASE_URL=' . $this->exampleBaseUrl
                )
            );
        }
        $this->baseUrl = $baseUrl;

        $timeout = env($this->configPrefix . '.timeout') ?: env($this->envPrefix . '_TIMEOUT');
        if ($timeout !== false && $timeout !== null && $timeout !== '') {
            $this->timeout = (int) $timeout;
        }

        $connectTimeout = env($this->configPrefix . '.connectTimeout') ?: env($this->envPrefix . '_CONNECT_TIMEOUT');
        if ($connectTimeout !== false && $connectTimeout !== null && $connectTimeout !== '') {
            $this->connectTimeout = (int) $connectTimeout;
        }

        $apiPrefix = env($this->configPrefix . '.apiPrefix') ?: env($this->envPrefix . '_PREFIX');
        if (is_string($apiPrefix) && trim($apiPrefix) !== '') {
            $normalizedPrefix = '/' . trim($apiPrefix, '/');
            $this->apiPrefix = $normalizedPrefix === '/' ? '/api/v1' : $normalizedPrefix;
        }

        $appName = env($this->configPrefix . '.appName') ?: env($this->envPrefix . '_APP_NAME');
        if (is_string($appName) && trim($appName) !== '') {
            $this->appName = $appName;
        }

        $appKey = env($this->configPrefix . '.appKey') ?: env($this->envPrefix . '_APP_KEY');
        if (is_string($appKey) && trim($appKey) !== '') {
            $this->appKey = $appKey;
        }

        $val = env($this->configPrefix . '.healthPaths') ?: env($this->envPrefix . '_HEALTH_PATHS');
        if ($val) {
            $paths = array_values(array_filter(array_map('trim', explode(',', (string) $val))));
            if ($paths !== []) {
                $this->healthPaths = $paths;
            }
        }

        $logRequests = env($this->configPrefix . '.logRequests') ?: env($this->envPrefix . '_LOG_REQUESTS');
        if ($logRequests !== null && $logRequests !== '') {
            $this->logRequests = filter_var($logRequests, FILTER_VALIDATE_BOOLEAN);
        }
    }
}
