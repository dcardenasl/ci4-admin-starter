<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Services;

use App\Services\BaseApiService;

class MetricsApiService extends BaseApiService implements MetricsApiServiceInterface
{
    public function summary(array $filters = []): array
    {
        return $this->apiClient->get('/metrics', $filters);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|list<array<string, mixed>>, raw: string, headers: array<string, string>, messages: list<string>, fieldErrors: array<string, string>}
     */
    public function timeseries(array $filters = []): array
    {
        $response = $this->apiClient->get('/metrics/timeseries', $filters);

        if (! ($response['ok'] ?? false)) {
            return $response;
        }

        // Transform parallel arrays (dates, requests, etc.) to a list of point objects
        $data = $response['data'] ?? [];
        if (is_array($data) && isset($data['dates']) && is_array($data['dates'])) {
            $points = [];
            foreach ($data['dates'] as $i => $date) {
                $points[] = [
                    'period'  => $date,
                    'value'   => $data['requests'][$i] ?? 0,
                    'errors'  => $data['errors'][$i] ?? 0,
                    'latency' => $data['latency'][$i] ?? 0,
                ];
            }
            $response['data'] = $points;
        }

        return $response;
    }
}
