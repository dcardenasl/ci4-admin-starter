<?php

declare(strict_types=1);

namespace Config;

/**
 * Configuration for an event-domain backend running alongside the primary
 * domain app. It intentionally uses its own environment namespace so an
 * admin instance can address both domain apps without routing one module to
 * the other domain's port.
 */
class EventDomainApiClient extends DomainApiClient
{
    protected string $configPrefix = 'eventDomainApiClient';

    protected string $envPrefix = 'EVENT_DOMAIN_API';

    protected string $backendLabel = 'event-domain API';

    protected string $exampleBaseUrl = 'http://localhost:8193';
}
