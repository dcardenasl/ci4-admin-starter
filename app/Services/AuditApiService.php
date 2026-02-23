<?php

namespace App\Services;

class AuditApiService extends ResourceApiService
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
