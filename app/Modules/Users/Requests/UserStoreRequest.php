<?php

declare(strict_types=1);

namespace App\Modules\Users\Requests;

use App\Requests\BaseFormRequest;

class UserStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['first_name', 'last_name', 'email', 'role'];
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[2]|max_length[100]',
            'email'     => 'required|valid_email',
            'role'      => 'required|in_list[user,admin,superadmin]',
        ];
    }

    public function payload(): array
    {
        return [
            'first_name' => $this->postString('first_name'),
            'last_name'  => $this->postString('last_name'),
            'email'     => $this->postString('email'),
            'role'      => $this->postString('role'),
        ];
    }
}
