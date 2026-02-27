<?php

namespace App\Requests\User;

use App\Requests\BaseFormRequest;

class UserUpdateRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['firstName', 'lastName', 'email', 'role', 'originalEmail'];
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
        $payload = [
            'firstName' => (string) $this->request->getPost('firstName'),
            'lastName'  => (string) $this->request->getPost('lastName'),
            'role'      => (string) $this->request->getPost('role'),
        ];

        $email = trim((string) $this->request->getPost('email'));
        $originalEmail = trim((string) $this->request->getPost('originalEmail'));

        if ($originalEmail === '' || mb_strtolower($email) !== mb_strtolower($originalEmail)) {
            $payload['email'] = $email;
        }

        return $payload;
    }
}
