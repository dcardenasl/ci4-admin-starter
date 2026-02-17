<?php

namespace App\Services;

class MetricsApiService extends BaseApiService
{
    public function get(): array
    {
        return $this->apiClient->get('/metrics');
    }
}
