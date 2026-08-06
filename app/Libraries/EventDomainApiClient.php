<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\EventDomainApiClient as EventDomainApiClientConfig;

/**
 * HTTP client targeting the event-domain backend.
 */
class EventDomainApiClient extends DomainApiClient implements EventDomainApiClientInterface
{
    public function __construct(?EventDomainApiClientConfig $config = null)
    {
        parent::__construct($config ?? config(EventDomainApiClientConfig::class));
    }
}
