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
        return [
            'file'       => $this->request->getFile('file'),
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
