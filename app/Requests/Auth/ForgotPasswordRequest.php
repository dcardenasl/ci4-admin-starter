<?php

namespace App\Requests\Auth;

use App\Requests\BaseFormRequest;

class ForgotPasswordRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['email'];
    }

    public function rules(): array
    {
        return [
            'email' => 'required|valid_email',
        ];
    }

    public function payload(): array
    {
        return [
            'email' => (string) $this->request->getPost('email'),
        ];
    }
}
