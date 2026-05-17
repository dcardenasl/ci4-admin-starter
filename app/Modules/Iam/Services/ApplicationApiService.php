<?php

declare(strict_types=1);

namespace App\Modules\Iam\Services;

use App\Services\ResourceApiService;

class ApplicationApiService extends ResourceApiService implements ApplicationApiServiceInterface
{
    protected function resourcePath(): string
    {
        return '/iam/applications';
    }
}
