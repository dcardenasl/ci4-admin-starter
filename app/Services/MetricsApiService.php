<?php

namespace App\Services;

class MetricsApiService extends BaseApiService
{
    public function get(array $filters = []): array
    {
        return $this->summary($filters);
    }

    public function summary(array $filters = []): array
    {
        return $this->apiClient->get('/metrics', $filters);
    }

    public function timeseries(array $filters = []): array
    {
        $response = $this->apiClient->get('/metrics/timeseries', $filters);

        if ($response['ok'] ?? false) {
            return $response;
        }

        return $this->apiClient->get('/metrics', $filters);
    }
}
