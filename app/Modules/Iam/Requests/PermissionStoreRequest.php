<?php

declare(strict_types=1);

namespace App\Modules\Iam\Requests;

use App\Support\Requests\BaseFormRequest;

class PermissionStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['application_id', 'code', 'resource', 'action', 'description'];
    }

    public function rules(): array
    {
        return [
            'application_id' => 'required|is_natural_no_zero',
            'code'           => 'required|min_length[2]|max_length[100]',
            'resource'       => 'required|min_length[1]|max_length[50]',
            'action'         => 'required|min_length[1]|max_length[50]',
            'description'    => 'permit_empty|max_length[500]',
        ];
    }

    public function payload(): array
    {
        return [
            'application_id' => $this->postInt('application_id'),
            'code'           => $this->postString('code'),
            'resource'       => $this->postString('resource'),
            'action'         => $this->postString('action'),
            'description'    => $this->postString('description'),
        ];
    }
}
