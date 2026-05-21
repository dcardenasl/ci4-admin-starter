<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Marker interface for the HTTP client that targets a ci4-bff-starter gateway.
 *
 * Functionally identical to {@see ApiClientInterface}; the separate symbol
 * exists so the DI container in {@see \Config\Services} can distinguish the
 * BFF client from the hub and domain clients without ambiguity.
 */
interface BffApiClientInterface extends ApiClientInterface
{
}
