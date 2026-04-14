<?php

declare(strict_types=1);

namespace App\Modules\Users\Services;

use App\Services\ResourceApiService;

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
