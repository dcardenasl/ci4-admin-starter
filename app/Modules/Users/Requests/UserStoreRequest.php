<?php

declare(strict_types=1);

namespace App\Modules\Users\Requests;

use App\Support\Requests\BaseFormRequest;

class UserStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['first_name', 'last_name', 'email', 'role_ids'];
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
            'email'      => 'required|valid_email',
            'role_ids'   => 'permit_empty',
        ];
    }

    public function payload(): array
    {
        return [
            'first_name' => $this->postString('first_name'),
            'last_name'  => $this->postString('last_name'),
            'email'      => $this->postString('email'),
            'role_ids'   => $this->normalizedRoleIds(),
        ];
    }

    /**
     * @return list<int>
     */
    private function normalizedRoleIds(): array
    {
        $raw = $this->request->getPost('role_ids');
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
