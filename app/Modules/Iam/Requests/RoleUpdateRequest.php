<?php

declare(strict_types=1);

namespace App\Modules\Iam\Requests;

class RoleUpdateRequest extends RoleStoreRequest
{
    public function payload(): array
    {
        $payload = [
            'application_id' => $this->normalizedApplicationId(),
            'code'           => $this->postString('code'),
            'name'           => $this->postString('name'),
            'description'    => $this->postString('description'),
        ];

        // Only forward `permission_ids` when the form actually posted it. This
        // mirrors UserUpdateRequest::payload() and lets the API distinguish
        // "no change" (omit) from "clear all" (empty array).
        if ($this->request->getPost('permission_ids') !== null) {
            $payload['permission_ids'] = $this->normalizedPermissionIds();
        }

        return $payload;
    }

    private function normalizedApplicationId(): ?int
    {
        $raw = $this->request->getPost('application_id');
        $val = is_scalar($raw) ? trim((string) $raw) : '';

        return $val === '' ? null : (int) $val;
    }
}
