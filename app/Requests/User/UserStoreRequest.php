<?php

namespace App\Requests\User;

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
            'email'      => 'required|valid_email',
            'role'       => 'required|in_list[user,admin,superadmin]',
        ];
    }

    public function payload(): array
    {
        return [
            'first_name' => (string) $this->request->getPost('first_name'),
            'last_name'  => (string) $this->request->getPost('last_name'),
            'email'      => (string) $this->request->getPost('email'),
            'role'       => (string) $this->request->getPost('role'),
        ];
    }
}
