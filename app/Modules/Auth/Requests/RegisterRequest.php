<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use App\Support\Requests\BaseFormRequest;

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
            'email'                => 'required|valid_email',
            'password'             => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ];
    }

    public function payload(): array
    {
        return [
            'first_name'            => $this->postString('first_name'),
            'last_name'             => $this->postString('last_name'),
            'email'                => $this->postString('email'),
            'password'             => $this->postString('password'),
            'password_confirmation' => $this->postString('password_confirmation'),
        ];
    }
}
