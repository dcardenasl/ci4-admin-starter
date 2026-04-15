<?php

declare(strict_types=1);

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
            'email'    => $this->postString('email'),
            'password' => $this->postString('password'),
        ];
    }
}
