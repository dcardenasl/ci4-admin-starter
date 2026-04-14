<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Dashboard\Services\HealthApiService as DashboardHealthApiService;

/**
 * Backward-compatible alias for the dashboard module health service.
 */
class HealthApiService extends DashboardHealthApiService implements HealthApiServiceInterface
{
}
