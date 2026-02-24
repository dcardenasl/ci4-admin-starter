<?php

namespace App\Requests\ApiKey;

class ApiKeyUpdateRequest extends ApiKeyStoreRequest
{
    public function rules(): array
    {
        return [
            'name'                => 'permit_empty|max_length[100]',
            'is_active'           => 'permit_empty|in_list[0,1]',
            'rate_limit_requests' => 'permit_empty|is_natural_no_zero',
            'rate_limit_window'   => 'permit_empty|is_natural_no_zero',
            'user_rate_limit'     => 'permit_empty|is_natural_no_zero',
            'ip_rate_limit'       => 'permit_empty|is_natural_no_zero',
        ];
    }
}
