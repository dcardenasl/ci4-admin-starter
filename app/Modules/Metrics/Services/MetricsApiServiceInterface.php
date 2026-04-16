<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
interface MetricsApiServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     * @return ApiResponse
     */
    public function summary(array $filters = []): array;

    /**
     * @param array<string, mixed> $filters
     * @return ApiResponse
     */
    public function timeseries(array $filters = []): array;
}
