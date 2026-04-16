<?php

declare(strict_types=1);

namespace App\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
abstract class ResourceApiService extends BaseApiService
{
    abstract protected function resourcePath(): string;

    /**
     * @param array<string, mixed> $filters
     * @return ApiResponse
     */
    public function list(array $filters = []): array
    {
        return $this->apiClient->get($this->resourcePath(), $filters);
    }

    /** @return ApiResponse */
    public function get(int|string $id): array
    {
        return $this->apiClient->get($this->resourcePath() . '/' . $id);
    }

    /**
     * @param array<string, mixed> $payload
     * @return ApiResponse
     */
    public function create(array $payload): array
    {
        return $this->apiClient->post($this->resourcePath(), $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return ApiResponse
     */
    public function update(int|string $id, array $payload): array
    {
        return $this->apiClient->put($this->resourcePath() . '/' . $id, $payload);
    }

    /** @return ApiResponse */
    public function delete(int|string $id): array
    {
        return $this->apiClient->delete($this->resourcePath() . '/' . $id);
    }
}
