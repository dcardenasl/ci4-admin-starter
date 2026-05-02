<?php

declare(strict_types=1);

namespace App\Modules\Iam\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
interface AppUserMembershipApiServiceInterface
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

    /** @return ApiResponse */
    public function listRoles(int|string $id): array;

    /**
     * @param list<int> $roleIds
     * @return ApiResponse
     */
    public function attachRoles(int|string $id, array $roleIds): array;

    /** @return ApiResponse */
    public function detachRole(int|string $id, int|string $roleId): array;

    /** @return ApiResponse */
    public function listForUser(int|string $userId): array;
}
