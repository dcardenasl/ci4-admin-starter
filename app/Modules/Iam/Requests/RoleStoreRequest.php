<?php

declare(strict_types=1);

namespace App\Modules\Iam\Requests;

use App\Support\Requests\BaseFormRequest;

class RoleStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['application_id', 'code', 'name', 'description'];
    }

    public function rules(): array
    {
        return [
            'application_id' => 'permit_empty|is_natural_no_zero',
            'code'           => 'required|min_length[2]|max_length[100]',
            'name'           => 'required|min_length[2]|max_length[100]',
            'description'    => 'permit_empty|max_length[500]',
        ];
    }

    public function payload(): array
    {
        $rawAppId = $this->request->getPost('application_id');
        $appIdRaw = is_scalar($rawAppId) ? trim((string) $rawAppId) : '';

        return [
            'application_id' => $appIdRaw === '' ? null : (int) $appIdRaw,
            'code'           => $this->postString('code'),
            'name'           => $this->postString('name'),
            'description'    => $this->postString('description'),
        ];
    }
}
