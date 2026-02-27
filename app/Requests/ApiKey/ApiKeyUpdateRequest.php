<?php

namespace App\Requests\ApiKey;

class ApiKeyUpdateRequest extends ApiKeyStoreRequest
{
    public function rules(): array
    {
        return [
            'name'              => 'permit_empty|max_length[100]',
            'isActive'          => 'permit_empty|in_list[0,1]',
            'rateLimitRequests' => 'permit_empty|is_natural_no_zero',
            'rateLimitWindow'   => 'permit_empty|is_natural_no_zero',
            'userRateLimit'     => 'permit_empty|is_natural_no_zero',
            'ipRateLimit'       => 'permit_empty|is_natural_no_zero',
        ];
    }
}
