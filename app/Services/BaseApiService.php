<?php

namespace App\Services;

use App\Libraries\ApiClientInterface;

abstract class BaseApiService
{
    public function __construct(protected ApiClientInterface $apiClient) {}
}
