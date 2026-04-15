<?php

declare(strict_types=1);

namespace App\Requests\File;

use App\Requests\BaseFormRequest;
use App\Support\FileSizeLimits;

class FileUploadRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['file'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function rules(): array
    {
        $maxBytes = FileSizeLimits::effectiveMaxBytes();
        $maxKb = max(1, (int) floor($maxBytes / 1024));

        return [
            'file' => [
                'label' => lang('Files.file_name'),
                'rules' => [
                    // Usamos una regla que no dispare el validador si el objeto ya es UploadedFile
                    // o simplemente confiamos en la validación manual del controlador para el objeto.
                    "max_size[file,{$maxKb}]",
                    'ext_in[file,png,jpg,jpeg,pdf,doc,docx,xls,xlsx,txt,zip]',
                ],
            ],
        ];
    }

    public function messages(): array
    {
        $maxMb = FileSizeLimits::bytesToMb(FileSizeLimits::effectiveMaxBytes());

        return [
            'file' => [
                'max_size' => lang('Files.file_too_large', [$maxMb]),
            ],
        ];
    }

    public function data(): array
    {
        $data = parent::data();
        $file = $this->request->getFile('file');
        if ($file && $file->isValid()) {
            $data['file'] = $file->getName();
        }
        return $data;
    }

    public function payload(): array
    {
        return [
            'visibility' => 'private',
        ];
    }
}
