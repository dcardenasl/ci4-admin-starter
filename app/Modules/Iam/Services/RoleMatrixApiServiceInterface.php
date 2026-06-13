<?php

declare(strict_types=1);

namespace App\Modules\Iam\Services;

interface RoleMatrixApiServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function matrix(): array;
}
