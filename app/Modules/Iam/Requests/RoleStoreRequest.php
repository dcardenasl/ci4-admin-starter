<?php

declare(strict_types=1);

namespace App\Modules\Iam\Requests;

use App\Support\Requests\BaseFormRequest;

class RoleStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['application_id', 'code', 'name', 'description', 'permission_ids'];
    }

    public function rules(): array
    {
        return [
            'application_id' => 'permit_empty|is_natural_no_zero',
            'code'           => 'required|min_length[2]|max_length[100]',
            'name'           => 'required|min_length[2]|max_length[100]',
            'description'    => 'permit_empty|max_length[500]',
            'permission_ids' => 'permit_empty',
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
            'permission_ids' => $this->normalizedPermissionIds(),
        ];
    }

    /**
     * @return list<int>
     */
    protected function normalizedPermissionIds(): array
    {
        $raw = $this->request->getPost('permission_ids');
        if (! is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach ($raw as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                $clean[] = (int) $value;
            }
        }
        return array_values(array_unique($clean));
    }
}
