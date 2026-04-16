<?php

declare(strict_types=1);

namespace App\Modules\ApiKeys\Services;

use App\Services\ResourceApiService;

class ApiKeyApiService extends ResourceApiService implements ApiKeyApiServiceInterface
{
    protected function resourcePath(): string
    {
        return '/api-keys';
    }
}
