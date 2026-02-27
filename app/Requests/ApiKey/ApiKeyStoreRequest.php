<?php

namespace App\Requests\ApiKey;

use App\Requests\BaseFormRequest;

class ApiKeyStoreRequest extends BaseFormRequest
{
    protected function fields(): array
    {
        return [
            'name',
            'isActive',
            'rateLimitRequests',
            'rateLimitWindow',
            'userRateLimit',
            'ipRateLimit',
        ];
    }

    public function rules(): array
    {
        return [
            'name'              => 'required|max_length[100]',
            'rateLimitRequests' => 'permit_empty|is_natural_no_zero',
            'rateLimitWindow'   => 'permit_empty|is_natural_no_zero',
            'userRateLimit'     => 'permit_empty|is_natural_no_zero',
            'ipRateLimit'       => 'permit_empty|is_natural_no_zero',
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

        $isActive = $this->request->getPost('isActive');
        if ($isActive !== null && $isActive !== '') {
            $payload['isActive'] = $isActive === '1' || $isActive === 1 || $isActive === true || $isActive === 'true';
        }

        $numericFields = [
            'rateLimitRequests',
            'rateLimitWindow',
            'userRateLimit',
            'ipRateLimit',
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
