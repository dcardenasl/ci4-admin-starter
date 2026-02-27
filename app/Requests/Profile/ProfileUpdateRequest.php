<?php

namespace App\Requests\Profile;

use App\Requests\BaseFormRequest;

class ProfileUpdateRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['firstName', 'lastName'];
    }

    public function rules(): array
    {
        return [
            'firstName' => 'required|min_length[2]|max_length[100]',
            'lastName'  => 'required|min_length[2]|max_length[100]',
        ];
    }

    public function payload(): array
    {
        return [
            'firstName' => (string) $this->request->getPost('firstName'),
            'lastName'  => (string) $this->request->getPost('lastName'),
        ];
    }
}
