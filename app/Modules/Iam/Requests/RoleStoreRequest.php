<?php

declare(strict_types=1);

namespace App\Modules\Iam\Requests;

use App\Support\Requests\BaseFormRequest;

class RoleStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['code', 'name', 'description'];
    }

    public function rules(): array
    {
        return [
            'code'        => 'required|min_length[2]|max_length[100]',
            'name'        => 'required|min_length[2]|max_length[100]',
            'description' => 'permit_empty|max_length[500]',
        ];
    }

    public function payload(): array
    {
        return [
            'code'        => $this->postString('code'),
            'name'        => $this->postString('name'),
            'description' => $this->postString('description'),
        ];
    }
}
