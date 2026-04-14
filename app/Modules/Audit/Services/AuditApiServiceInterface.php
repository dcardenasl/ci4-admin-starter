<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
interface AuditApiServiceInterface
{
    /** @return ApiResponse */
    public function list(array $filters = []): array;

    /** @return ApiResponse */
    public function get(int|string $id): array;

    /** @return ApiResponse */
    public function create(array $payload): array;

    /** @return ApiResponse */
    public function update(int|string $id, array $payload): array;

    /** @return ApiResponse */
    public function delete(int|string $id): array;

    /** @return ApiResponse */
    public function byEntity(string $type, int|string $id): array;
}
