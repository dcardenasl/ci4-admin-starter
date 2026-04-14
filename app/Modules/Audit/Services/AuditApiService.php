<?php

declare(strict_types=1);

namespace AppModulesAuditServices;

class AuditApiService extends ResourceApiService
{
    protected function resourcePath(): string
    {
        return '/audit';
    }

    public function byEntity(string $type, int|string $id): array
    {
        return $this->apiClient->get('/audit/entity/' . $type . '/' . $id);
    }
}
