<?php

declare(strict_types=1);

namespace AppModulesApiKeysServices;

class ApiKeyApiService extends ResourceApiService
{
    protected function resourcePath(): string
    {
        return '/api-keys';
    }
}
