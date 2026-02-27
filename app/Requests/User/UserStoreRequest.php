<?php

namespace App\Requests\User;

use App\Requests\BaseFormRequest;

class UserStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['firstName', 'lastName', 'email', 'role', 'sendInvite', 'clientBaseUrl'];
    }

    public function rules(): array
    {
        return [
            'firstName'     => 'required|min_length[2]|max_length[100]',
            'lastName'      => 'required|min_length[2]|max_length[100]',
            'email'         => 'required|valid_email',
            'role'          => 'required|in_list[user,admin,superadmin]',
            'sendInvite'    => 'permit_empty|in_list[0,1,on,off,true,false]',
            'clientBaseUrl' => 'permit_empty|valid_url',
        ];
    }

    public function payload(): array
    {
        $sendInvite = $this->request->getPost('sendInvite');

        return [
            'firstName'     => (string) $this->request->getPost('firstName'),
            'lastName'      => (string) $this->request->getPost('lastName'),
            'email'         => (string) $this->request->getPost('email'),
            'role'          => (string) $this->request->getPost('role'),
            'sendInvite'    => $sendInvite === 'on' || $sendInvite === '1' || $sendInvite === 'true',
            'clientBaseUrl' => (string) $this->request->getPost('clientBaseUrl'),
        ];
    }
}
