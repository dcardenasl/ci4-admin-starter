<?php

namespace App\Requests\ApiKey;

use App\Requests\BaseFormRequest;

class ApiKeyStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return [
            'name',
            'is_active',
            'rate_limit_requests',
            'rate_limit_window',
            'user_rate_limit',
            'ip_rate_limit',
        ];
    }

    public function rules(): array
    {
        return [
            'name'                => 'required|max_length[100]',
            'rate_limit_requests' => 'permit_empty|is_natural_no_zero',
            'rate_limit_window'   => 'permit_empty|is_natural_no_zero',
            'user_rate_limit'     => 'permit_empty|is_natural_no_zero',
            'ip_rate_limit'       => 'permit_empty|is_natural_no_zero',
        ];
    }

    public function payload(): array
    {
        return $this->buildPayload();
    }

    /**
     * @return array<string, int|string|bool>
     */
    protected function buildPayload(): array
    {
        $payload = [];

        $name = trim((string) $this->request->getPost('name'));
        if ($name !== '') {
            $payload['name'] = $name;
        }

        $isActive = $this->request->getPost('is_active');
        if ($isActive !== null && $isActive !== '') {
            $payload['is_active'] = $isActive === '1' || $isActive === 1 || $isActive === true || $isActive === 'true';
        }

        $numericFields = [
            'rate_limit_requests',
            'rate_limit_window',
            'user_rate_limit',
            'ip_rate_limit',
        ];

        foreach ($numericFields as $field) {
            $value = trim((string) $this->request->getPost($field));
            if ($value !== '') {
                $payload[$field] = (int) $value;
            }
        }

        return $payload;
    }
}
