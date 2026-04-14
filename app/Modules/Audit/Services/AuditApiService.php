<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

use App\Services\ResourceApiService;

class AuditApiService extends ResourceApiService implements AuditApiServiceInterface
{
    protected function resourcePath(): string
    {
        return '/audit';
    }

    public function byEntity(string $type, int|string $id): array
    {
        return $this->apiClient->get('/audit/entity/' . $type . '/' . $id);
    }
}
