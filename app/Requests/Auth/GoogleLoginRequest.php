<?php

namespace App\Requests\Auth;

use App\Requests\BaseFormRequest;

class GoogleLoginRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['idToken'];
    }

    public function rules(): array
    {
        return [
            'idToken' => 'required|string|max_length[4096]',
        ];
    }

    public function payload(): array
    {
        return [
            'idToken' => trim((string) $this->request->getPost('idToken')),
        ];
    }
}
