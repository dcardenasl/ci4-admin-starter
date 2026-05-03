<?php

declare(strict_types=1);

namespace App\Modules\Iam\Requests;

use App\Support\Requests\BaseFormRequest;

class AppUserMembershipStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return ['user_id', 'application_id', 'status'];
    }

    public function rules(): array
    {
        return [
            'user_id'        => 'required|is_natural_no_zero',
            'application_id' => 'required|is_natural_no_zero',
            'status'         => 'permit_empty|max_length[255]',
        ];
    }

    public function payload(): array
    {
        return [
            'user_id'        => $this->postInt('user_id'),
            'application_id' => $this->postInt('application_id'),
            'status'         => $this->postString('status'),
        ];
    }
}
