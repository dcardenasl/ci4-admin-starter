<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\BffApiClient as BffApiClientConfig;

/**
 * HTTP client targeting a ci4-bff-starter gateway.
 *
 * Reuses every behaviour of {@see ApiClient} (auth header injection,
 * token refresh, app-key forwarding, upload handling) but defaults to the
 * `BffApiClient` config instead of `ApiClient`.
 */
class BffApiClient extends ApiClient implements BffApiClientInterface
{
    public function __construct(?BffApiClientConfig $config = null)
    {
        parent::__construct($config ?? config(BffApiClientConfig::class));
    }
}
