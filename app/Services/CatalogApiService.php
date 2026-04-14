<?php

declare(strict_types=1);

namespace App\Services;

class CatalogApiService extends BaseApiService
{
    public function index(): array
    {
        return $this->apiClient->get('/catalogs');
    }

    public function auditFacets(int $windowDays = 90, int $limit = 100): array
    {
        return $this->apiClient->get('/catalogs/audit-facets', [
            'window_days' => $windowDays,
            'limit'       => $limit,
        ]);
    }
}
