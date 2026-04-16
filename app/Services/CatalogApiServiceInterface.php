<?php

declare(strict_types=1);

namespace App\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
interface CatalogApiServiceInterface
{
    /** @return ApiResponse */
    public function index(): array;

    /** @return ApiResponse */
    public function auditFacets(int $windowDays = 90, int $limit = 100): array;
}
