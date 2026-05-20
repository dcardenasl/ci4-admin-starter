<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\DomainApiClient as DomainApiClientConfig;

/**
 * Secondary HTTP client targeting a domain-starter app.
 *
 * Reuses every behaviour of {@see ApiClient} (auth header injection,
 * token refresh, app-key forwarding, upload handling) but defaults to the
 * `DomainApiClient` config instead of `ApiClient`.
 */
class DomainApiClient extends ApiClient implements DomainApiClientInterface
{
    public function __construct(?DomainApiClientConfig $config = null)
    {
        parent::__construct($config ?? config(DomainApiClientConfig::class));
    }
}
