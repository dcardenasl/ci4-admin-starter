<?php

namespace App\Requests\Auth;

use App\Requests\BaseFormRequest;

class ResetPasswordRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['token', 'email', 'password', 'password_confirmation'];
    }

    public function rules(): array
    {
        return [
            'token'                 => 'required',
            'email'                 => 'required|valid_email',
            'password'              => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ];
    }

    public function payload(): array
    {
        return [
            'token'                 => (string) $this->request->getPost('token'),
            'email'                 => (string) $this->request->getPost('email'),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];
    }
}
