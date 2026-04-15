<?php

declare(strict_types=1);

namespace App\Modules\Users\Requests;

use App\Requests\BaseFormRequest;

class UserUpdateRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['first_name', 'last_name', 'email', 'role', 'original_email'];
    }

    public function rules(): array
    {
        return [
            'first_name'     => 'required|min_length[2]|max_length[100]',
            'last_name'      => 'required|min_length[2]|max_length[100]',
            'email'          => 'required|valid_email',
            'role'           => 'required|in_list[user,admin,superadmin]',
            'original_email' => 'required|valid_email',
        ];
    }

    public function payload(): array
    {
        $payload = [
            'first_name' => $this->postString('first_name'),
            'last_name'  => $this->postString('last_name'),
            'role'      => $this->postString('role'),
        ];

        $email = trim($this->postString('email'));
        $original_email = trim($this->postString('original_email'));

        if ($original_email === '' || mb_strtolower($email) !== mb_strtolower($original_email)) {
            $payload['email'] = $email;
        }

        return $payload;
    }
}
