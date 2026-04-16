<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
interface HealthApiServiceInterface
{
    /** @return ApiResponse */
    public function check(): array;
}
