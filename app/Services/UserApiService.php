<?php

namespace App\Services;

class UserApiService extends BaseApiService
{
    public function list(array $filters = []): array
    {
        return $this->apiClient->get('/users', $filters);
    }

    public function get(int|string $id): array
    {
        return $this->apiClient->get('/users/' . $id);
    }

    public function create(array $payload): array
    {
        return $this->apiClient->post('/users', $payload);
    }

    public function update(int|string $id, array $payload): array
    {
        return $this->apiClient->put('/users/' . $id, $payload);
    }

    public function delete(int|string $id): array
    {
        return $this->apiClient->delete('/users/' . $id);
    }

    public function approve(int|string $id): array
    {
        return $this->apiClient->post('/users/' . $id . '/approve');
    }
}
