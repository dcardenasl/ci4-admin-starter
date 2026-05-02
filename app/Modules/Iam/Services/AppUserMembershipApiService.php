<?php

declare(strict_types=1);

namespace App\Modules\Iam\Services;

use App\Services\ResourceApiService;

class AppUserMembershipApiService extends ResourceApiService implements AppUserMembershipApiServiceInterface
{
    protected function resourcePath(): string
    {
        return '/api/v1/iam/memberships';
    }

    public function listRoles(int|string $id): array
    {
        return $this->apiClient->get($this->resourcePath() . '/' . $id . '/roles');
    }

    public function attachRoles(int|string $id, array $roleIds): array
    {
        return $this->apiClient->post(
            $this->resourcePath() . '/' . $id . '/roles/attach',
            ['role_ids' => array_values(array_map(static fn ($v) => (int) $v, $roleIds))],
        );
    }

    public function detachRole(int|string $id, int|string $roleId): array
    {
        return $this->apiClient->delete($this->resourcePath() . '/' . $id . '/roles/' . $roleId);
    }

    public function listForUser(int|string $userId): array
    {
        return $this->apiClient->get('/api/v1/iam/users/' . $userId . '/memberships');
    }
}
