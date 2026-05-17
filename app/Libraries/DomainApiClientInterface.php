<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Marker interface for the secondary HTTP client that targets a domain-starter
 * app instead of the hub.
 *
 * Functionally identical to {@see ApiClientInterface}; the separate symbol
 * exists so the DI container in {@see \Config\Services} can distinguish the
 * two clients without ambiguity, and so PHPStan can flag a service that was
 * accidentally wired to the wrong backend.
 */
interface DomainApiClientInterface extends ApiClientInterface
{
}
