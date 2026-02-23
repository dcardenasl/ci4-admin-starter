<?php

namespace App\Services;

abstract class ResourceApiService extends BaseApiService
{
    abstract protected function resourcePath(): string;

    public function list(array $filters = []): array
    {
        return $this->apiClient->get($this->resourcePath(), $filters);
    }

    public function get(int|string $id): array
    {
        return $this->apiClient->get($this->resourcePath() . '/' . $id);
    }

    public function create(array $payload): array
    {
        return $this->apiClient->post($this->resourcePath(), $payload);
    }

    public function update(int|string $id, array $payload): array
    {
        return $this->apiClient->put($this->resourcePath() . '/' . $id, $payload);
    }

    public function delete(int|string $id): array
    {
        return $this->apiClient->delete($this->resourcePath() . '/' . $id);
    }
}
