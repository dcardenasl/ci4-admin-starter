<?php

namespace App\Requests\User;

use App\Requests\BaseFormRequest;

class UserStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['first_name', 'last_name', 'email', 'role', 'send_invite', 'client_base_url'];
    }

    public function rules(): array
    {
        return [
            'first_name'      => 'required|min_length[2]|max_length[100]',
            'last_name'       => 'required|min_length[2]|max_length[100]',
            'email'           => 'required|valid_email',
            'role'            => 'required|in_list[user,admin,superadmin]',
            'send_invite'     => 'permit_empty|in_list[0,1,on,off,true,false]',
            'client_base_url' => 'permit_empty|valid_url',
        ];
    }

    public function payload(): array
    {
        $sendInvite = $this->request->getPost('send_invite');

        return [
            'first_name'      => (string) $this->request->getPost('first_name'),
            'last_name'       => (string) $this->request->getPost('last_name'),
            'email'           => (string) $this->request->getPost('email'),
            'role'            => (string) $this->request->getPost('role'),
            'send_invite'     => $sendInvite === 'on' || $sendInvite === '1' || $sendInvite === 'true',
            'client_base_url' => (string) $this->request->getPost('client_base_url'),
        ];
    }
}
