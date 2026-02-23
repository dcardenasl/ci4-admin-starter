<?php

namespace App\Services;

use RuntimeException;

class FileApiService extends ResourceApiService
{
    protected function resourcePath(): string
    {
        return '/files';
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
        return $this->get($id);
    }
}
