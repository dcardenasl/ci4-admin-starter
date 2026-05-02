<?php

declare(strict_types=1);

namespace App\Modules\Iam\Services;

use App\Services\ResourceApiService;

class RoleApiService extends ResourceApiService implements RoleApiServiceInterface
{
    protected function resourcePath(): string
    {
        return '/api/v1/iam/roles';
    }
}
