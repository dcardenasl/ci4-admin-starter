<?php

namespace App\Requests\Auth;

use App\Requests\BaseFormRequest;

class GoogleLoginRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['id_token'];
    }

    public function rules(): array
    {
        return [
            'id_token' => 'required|string|max_length[4096]',
        ];
    }

    public function payload(): array
    {
        return [
            'id_token' => trim((string) $this->request->getPost('id_token')),
        ];
    }
}
