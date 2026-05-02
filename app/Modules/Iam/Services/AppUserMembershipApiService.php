<?php

declare(strict_types=1);

namespace App\Modules\Iam\Services;

use App\Services\ResourceApiService;

class AppUserMembershipApiService extends ResourceApiService implements AppUserMembershipApiServiceInterface
{
    protected function resourcePath(): string
    {
        return '/api/v1/iam/memberships';
    }
}
