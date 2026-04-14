<?php

declare(strict_types=1);

namespace AppModulesMetricsServices;

class MetricsApiService extends BaseApiService
{
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
