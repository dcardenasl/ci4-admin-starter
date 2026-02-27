<?php

namespace App\Requests\Auth;

use App\Requests\BaseFormRequest;

class RegisterRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['firstName', 'lastName', 'email', 'password', 'passwordConfirmation'];
    }

    public function rules(): array
    {
        return [
            'firstName'            => 'required|min_length[2]|max_length[100]',
            'lastName'             => 'required|min_length[2]|max_length[100]',
            'email'                => 'required|valid_email',
            'password'             => 'required|min_length[8]',
            'passwordConfirmation' => 'required|matches[password]',
        ];
    }

    public function payload(): array
    {
        return [
            'firstName'            => (string) $this->request->getPost('firstName'),
            'lastName'             => (string) $this->request->getPost('lastName'),
            'email'                => (string) $this->request->getPost('email'),
            'password'             => (string) $this->request->getPost('password'),
            'passwordConfirmation' => (string) $this->request->getPost('passwordConfirmation'),
        ];
    }
}
