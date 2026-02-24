<?php

namespace App\Requests\Auth;

use App\Requests\BaseFormRequest;

class LoginRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['email', 'password'];
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];
    }

    public function payload(): array
    {
        return [
            'email'    => (string) $this->request->getPost('email'),
            'password' => (string) $this->request->getPost('password'),
        ];
    }
}
