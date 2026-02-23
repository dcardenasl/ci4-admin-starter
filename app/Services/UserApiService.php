<?php

namespace App\Services;

class UserApiService extends ResourceApiService
{
    protected function resourcePath(): string
    {
        return '/users';
    }

    public function approve(int|string $id): array
    {
        return $this->apiClient->post('/users/' . $id . '/approve');
    }
}
