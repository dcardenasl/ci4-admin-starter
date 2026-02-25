<?php

namespace App\Requests\File;

use App\Requests\BaseFormRequest;

class FileUploadRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['visibility'];
    }

    public function rules(): array
    {
        $maxBytes = config('Validation')->maxFileSizeBytes ?? 10485760;
        $maxKb    = ceil($maxBytes / 1024);

        return [
            'file' => "uploaded[file]|max_size[file,{$maxKb}]",
        ];
    }

    public function messages(): array
    {
        $maxBytes  = config('Validation')->maxFileSizeBytes ?? 10485760;
        $maxSizeMb = round($maxBytes / 1024 / 1024, 1);

        return [
            'file' => [
                'uploaded' => lang('Files.invalidFile'),
                'max_size' => lang('Files.fileTooLarge', [$maxSizeMb]),
            ],
        ];
    }

    public function data(): array
    {
        $file = $this->request->getPost('file');

        return [
            // CI4 file rules expect null|string as the value being validated.
            'file'       => is_string($file) ? $file : null,
            'visibility' => $this->request->getPost('visibility'),
        ];
    }

    public function payload(): array
    {
        return [
            'visibility' => (string) ($this->request->getPost('visibility') ?? 'private'),
        ];
    }
}
