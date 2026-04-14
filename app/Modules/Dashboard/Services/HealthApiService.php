<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Services\BaseApiService;

class HealthApiService extends BaseApiService implements HealthApiServiceInterface
{
    public function check(): array
    {
        try {
            return $this->apiClient->get('/health');
        } catch (\Throwable) {
            return [
                'ok' => false,
                'status' => 503,
                'data' => ['state' => 'down'],
                'raw' => '',
                'headers' => [],
                'messages' => ['API is unavailable'],
                'fieldErrors' => [],
            ];
        }
    }
}
