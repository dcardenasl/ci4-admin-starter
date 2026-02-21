<?php

namespace App\Services;

use RuntimeException;

class FileApiService extends BaseApiService
{
    public function list(array $filters = []): array
    {
        return $this->apiClient->get('/files', $filters);
    }

    public function upload(string $inputName, string $filePath, array $meta = []): array
    {
        if (! is_file($filePath)) {
            throw new RuntimeException("File does not exist: {$filePath}");
        }

        return $this->apiClient->upload('/files/upload', [$inputName => $filePath], $meta);
    }

    public function getDownload(int|string $id): array
    {
        return $this->apiClient->get('/files/' . $id);
    }

    public function delete(int|string $id): array
    {
        return $this->apiClient->delete('/files/' . $id);
    }
}
