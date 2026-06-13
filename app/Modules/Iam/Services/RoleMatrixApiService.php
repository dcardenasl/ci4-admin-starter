<?php

declare(strict_types=1);

namespace App\Modules\Iam\Services;

use App\Services\BaseApiService;

class RoleMatrixApiService extends BaseApiService implements RoleMatrixApiServiceInterface
{
    public function matrix(): array
    {
        return $this->apiClient->get('/api/v1/iam/role-permission-matrix');
    }
}
