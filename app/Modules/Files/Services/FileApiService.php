<?php

declare(strict_types=1);

namespace App\Modules\Files\Services;

use App\Services\ResourceApiService;
use RuntimeException;

class FileApiService extends ResourceApiService implements FileApiServiceInterface
{
    protected function resourcePath(): string
    {
        return '/files';
    }

    /**
     * Upload a file to the API using multipart form data.
     */
    public function upload(string $inputName, string $filePath, string $filename, ?string $mimeType = null, array $fields = []): array
    {
        if (! is_file($filePath)) {
            throw new RuntimeException("File does not exist: {$filePath}");
        }

        return $this->apiClient->upload('/files/upload', [
            $inputName => [
                'path'     => $filePath,
                'filename' => $filename,
                'mimeType' => $mimeType,
            ],
        ], $fields);
    }

    public function listForPicker(array $filters = []): array
    {
        return $this->apiClient->get('/files', $filters);
    }

    public function getInfo(int|string $id): array
    {
        return $this->apiClient->get('/files/' . $id . '/info');
    }

    public function updateMetadata(int|string $id, array $payload): array
    {
        return $this->apiClient->patch('/files/' . $id, $payload);
    }

    public function restore(int|string $id): array
    {
        return $this->apiClient->post('/files/' . $id . '/restore');
    }

    public function forceDelete(int|string $id): array
    {
        return $this->apiClient->delete('/files/' . $id . '/force');
    }

    public function usages(int|string $id): array
    {
        return $this->apiClient->get('/files/' . $id . '/usages');
    }

    public function replace(int|string $id, string $filePath, string $filename, ?string $mimeType = null): array
    {
        if (! is_file($filePath)) {
            throw new RuntimeException("File does not exist: {$filePath}");
        }

        return $this->apiClient->upload('/files/' . $id . '/replace', [
            'file' => [
                'path'     => $filePath,
                'filename' => $filename,
                'mimeType' => $mimeType,
            ],
        ]);
    }

    public function regenerateVariants(int|string $id): array
    {
        return $this->apiClient->post('/files/' . $id . '/regenerate-variants');
    }

    public function bulkDelete(array $ids): array
    {
        return $this->apiClient->post('/files/bulk-delete', ['ids' => array_values($ids)]);
    }

    public function bulkRestore(array $ids): array
    {
        return $this->apiClient->post('/files/bulk-restore', ['ids' => array_values($ids)]);
    }

    public function bulkForceDelete(array $ids): array
    {
        return $this->apiClient->post('/files/bulk-force-delete', ['ids' => array_values($ids)]);
    }
}
