<?php

namespace App\Services;

use RuntimeException;

class FileApiService extends ResourceApiService
{
    protected function resourcePath(): string
    {
        return '/files';
    }

    /**
     * Upload a file to the API using Base64 encoding.
     * Base64 is used for maximum reliability across different server configurations.
     */
    public function upload(string $inputName, string $filePath, string $filename, ?string $mimeType = null, array $fields = []): array
    {
        if (! is_file($filePath)) {
            throw new RuntimeException("File does not exist: {$filePath}");
        }

        $fileData = file_get_contents($filePath);
        if ($fileData === false) {
            throw new RuntimeException("Could not read file: {$filePath}");
        }

        if ($mimeType === null) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($filePath);
        }

        $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($fileData);

        // We use standard POST with Base64 payload instead of Multipart
        return $this->apiClient->post('/files/upload', array_merge($fields, [
            'file'     => $base64,
            'filename' => $filename,
        ]));
    }

    public function getDownload(int|string $id): array
    {
        return $this->get($id);
    }
}
