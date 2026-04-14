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
}
