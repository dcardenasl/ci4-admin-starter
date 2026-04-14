<?php

declare(strict_types=1);

namespace App\Services;

// Re-export the dashboard module's interface for backward compatibility
use App\Modules\Dashboard\Services\HealthApiServiceInterface as DashboardHealthApiServiceInterface;

/**
 * Alias for App\Modules\Dashboard\Services\HealthApiServiceInterface.
 * Used to maintain a consistent service interface location.
 */
interface HealthApiServiceInterface extends DashboardHealthApiServiceInterface
{
}
