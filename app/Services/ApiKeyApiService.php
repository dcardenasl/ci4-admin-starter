<?php

namespace App\Services;

class ApiKeyApiService extends ResourceApiService
{
    protected function resourcePath(): string
    {
        return '/api-keys';
    }
}
