<?php

namespace App\Requests\Auth;

use App\Requests\BaseFormRequest;

class RegisterRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['first_name', 'last_name', 'email', 'password', 'password_confirmation'];
    }

    public function rules(): array
    {
        return [
            'first_name'            => 'required|min_length[2]|max_length[100]',
            'last_name'             => 'required|min_length[2]|max_length[100]',
            'email'                 => 'required|valid_email',
            'password'              => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ];
    }

    public function payload(): array
    {
        return [
            'first_name'            => (string) $this->request->getPost('first_name'),
            'last_name'             => (string) $this->request->getPost('last_name'),
            'email'                 => (string) $this->request->getPost('email'),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];
    }
}
