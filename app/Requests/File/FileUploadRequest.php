<?php

namespace App\Requests\File;

use App\Requests\BaseFormRequest;

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
        $maxBytes = config('Validation')->maxFileSizeBytes ?? 10485760;
        $maxKb = (int) round($maxBytes / 1024);

        return [
            'file' => [
                'label' => lang('Files.fileName'),
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
        return [
            'file' => [
                'max_size' => lang('Files.fileTooLarge', [round((config('Validation')->maxFileSizeBytes ?? 10485760) / 1024 / 1024, 1)]),
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
