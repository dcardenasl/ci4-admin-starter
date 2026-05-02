<?php

declare(strict_types=1);

namespace App\Modules\Iam\Requests;

use App\Support\Requests\BaseFormRequest;

class RoleStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['name'];
    }

    public function rules(): array
    {
        return [
            'name' => 'required|min_length[2]|max_length[255]',
        ];
    }

    public function payload(): array
    {
        return [
            'name' => $this->postString('name'),
        ];
    }
}
