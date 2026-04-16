<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

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
            'token'                => 'required',
            'email'                => 'required|valid_email',
            'password'             => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ];
    }

    public function payload(): array
    {
        return [
            'token'                => $this->postString('token'),
            'email'                => $this->postString('email'),
            'password'             => $this->postString('password'),
            'password_confirmation' => $this->postString('password_confirmation'),
        ];
    }
}
