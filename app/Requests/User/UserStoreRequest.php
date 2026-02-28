<?php

namespace App\Requests\User;

use App\Requests\BaseFormRequest;

class UserStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['firstName', 'lastName', 'email', 'role'];
    }

    public function rules(): array
    {
        return [
            'firstName' => 'required|min_length[2]|max_length[100]',
            'lastName'  => 'required|min_length[2]|max_length[100]',
            'email'     => 'required|valid_email',
            'role'      => 'required|in_list[user,admin,superadmin]',
        ];
    }

    public function payload(): array
    {
        return [
            'firstName' => (string) $this->request->getPost('firstName'),
            'lastName'  => (string) $this->request->getPost('lastName'),
            'email'     => (string) $this->request->getPost('email'),
            'role'      => (string) $this->request->getPost('role'),
        ];
    }
}
