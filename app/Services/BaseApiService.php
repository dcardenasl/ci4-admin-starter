<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\ApiClientInterface;

abstract class BaseApiService
{
    public function __construct(protected ApiClientInterface $apiClient)
    {
    }
}
