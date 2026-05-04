<?php

declare(strict_types=1);

namespace App\Modules\Users\Services;

use App\Modules\Iam\Support\IamLookups;
use App\Services\ResourceApiService;

class UserApiService extends ResourceApiService implements UserApiServiceInterface
{
    protected function resourcePath(): string
    {
        return '/users';
    }

    public function create(array $payload): array
    {
        $response = parent::create($payload);
        $this->invalidateLookupsOnSuccess($response);

        return $response;
    }

    public function update(int|string $id, array $payload): array
    {
        $response = parent::update($id, $payload);
        $this->invalidateLookupsOnSuccess($response);

        return $response;
    }

    public function delete(int|string $id): array
    {
        $response = parent::delete($id);
        $this->invalidateLookupsOnSuccess($response);

        return $response;
    }

    public function approve(int|string $id): array
    {
        $response = $this->apiClient->post('/users/' . $id . '/approve');
        $this->invalidateLookupsOnSuccess($response);

        return $response;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function invalidateLookupsOnSuccess(array $response): void
    {
        $status = (int) ($response['status'] ?? 0);
        if ($status >= 200 && $status < 300) {
            IamLookups::invalidateUsers();
        }
    }
}
