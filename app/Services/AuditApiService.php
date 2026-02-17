<?php

namespace App\Services;

class AuditApiService extends BaseApiService
{
    public function list(array $filters = []): array
    {
        return $this->apiClient->get('/audit', $filters);
    }

    public function get(int|string $id): array
    {
        return $this->apiClient->get('/audit/' . $id);
    }

    public function byEntity(string $type, int|string $id): array
    {
        return $this->apiClient->get('/audit/entity/' . $type . '/' . $id);
    }
}
