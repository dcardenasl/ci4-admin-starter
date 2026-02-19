<?php

namespace App\Services;

class ApiKeyApiService extends BaseApiService
{
    public function list(array $filters = []): array
    {
        return $this->apiClient->get('/api-keys', $filters);
    }

    public function get(int|string $id): array
    {
        return $this->apiClient->get('/api-keys/' . $id);
    }

    public function create(array $payload): array
    {
        return $this->apiClient->post('/api-keys', $payload);
    }

    public function update(int|string $id, array $payload): array
    {
        return $this->apiClient->put('/api-keys/' . $id, $payload);
    }

    public function delete(int|string $id): array
    {
        return $this->apiClient->delete('/api-keys/' . $id);
    }
}
