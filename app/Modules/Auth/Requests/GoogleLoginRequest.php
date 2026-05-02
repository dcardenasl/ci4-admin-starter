<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use App\Support\Requests\BaseFormRequest;

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
            'id_token' => trim($this->postString('id_token')),
        ];
    }
}
