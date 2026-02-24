<?php

namespace App\Requests\User;

use App\Requests\BaseFormRequest;

class UserUpdateRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['first_name', 'last_name', 'email', 'role', 'original_email'];
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
        $payload = [
            'first_name' => (string) $this->request->getPost('first_name'),
            'last_name'  => (string) $this->request->getPost('last_name'),
            'role'       => (string) $this->request->getPost('role'),
        ];

        $email = trim((string) $this->request->getPost('email'));
        $originalEmail = trim((string) $this->request->getPost('original_email'));

        if ($originalEmail === '' || mb_strtolower($email) !== mb_strtolower($originalEmail)) {
            $payload['email'] = $email;
        }

        return $payload;
    }
}
