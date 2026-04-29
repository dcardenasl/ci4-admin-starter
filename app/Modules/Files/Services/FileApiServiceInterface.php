<?php

declare(strict_types=1);

namespace App\Modules\Files\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
interface FileApiServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     * @return ApiResponse
     */
    public function list(array $filters = []): array;

    /** @return ApiResponse */
    public function get(int|string $id): array;

    /**
     * @param array<string, mixed> $payload
     * @return ApiResponse
     */
    public function create(array $payload): array;

    /**
     * @param array<string, mixed> $payload
     * @return ApiResponse
     */
    public function update(int|string $id, array $payload): array;

    /** @return ApiResponse */
    public function delete(int|string $id): array;

    /**
     * @param array<string, mixed> $fields
     * @return ApiResponse
     */
    public function upload(string $inputName, string $filePath, string $filename, ?string $mimeType = null, array $fields = []): array;

    /**
     * @param array<string, mixed> $filters
     * @return ApiResponse
     */
    public function listForPicker(array $filters = []): array;

    /** @return ApiResponse */
    public function getInfo(int|string $id): array;

    /**
     * @param array<string, mixed> $payload
     * @return ApiResponse
     */
    public function updateMetadata(int|string $id, array $payload): array;

    /** @return ApiResponse */
    public function restore(int|string $id): array;

    /** @return ApiResponse */
    public function forceDelete(int|string $id): array;

    /** @return ApiResponse */
    public function usages(int|string $id): array;

    /** @return ApiResponse */
    public function replace(int|string $id, string $filePath, string $filename, ?string $mimeType = null): array;

    /** @return ApiResponse */
    public function regenerateVariants(int|string $id): array;

    /**
     * @param list<int|string> $ids
     * @return ApiResponse
     */
    public function bulkDelete(array $ids): array;

    /**
     * @param list<int|string> $ids
     * @return ApiResponse
     */
    public function bulkRestore(array $ids): array;

    /**
     * @param list<int|string> $ids
     * @return ApiResponse
     */
    public function bulkForceDelete(array $ids): array;
}
