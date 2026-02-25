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
        return [
            'file' => 'uploaded[file]|max_size[file,10240]',
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
