<?php

namespace App\Services;

class ReportApiService extends BaseApiService
{
    public function list(array $filters = []): array
    {
        return $this->apiClient->get('/reports', $filters);
    }

    public function exportCsv(array $filters = []): array
    {
        return $this->apiClient->get('/reports/export/csv', $filters);
    }

    public function exportPdf(array $filters = []): array
    {
        return $this->apiClient->get('/reports/export/pdf', $filters);
    }
}
