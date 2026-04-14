<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
interface MetricsApiServiceInterface
{
    /** @return ApiResponse */
    public function summary(array $filters = []): array;

    /** @return ApiResponse */
    public function timeseries(array $filters = []): array;
}
