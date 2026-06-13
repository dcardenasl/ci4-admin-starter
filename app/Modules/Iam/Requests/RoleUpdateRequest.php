<?php

declare(strict_types=1);

namespace App\Modules\Iam\Requests;

class RoleUpdateRequest extends RoleStoreRequest
{
    public function payload(): array
    {
        return [
            'code'        => $this->postString('code'),
            'name'        => $this->postString('name'),
            'description' => $this->postString('description'),
        ];
    }
}
