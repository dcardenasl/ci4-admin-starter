<?php

declare(strict_types=1);

namespace App\Support;

enum SessionKeys: string
{
    case ACCESS_TOKEN  = 'access_token';
    case REFRESH_TOKEN = 'refresh_token';
    case EXPIRES_AT    = 'token_expires_at';
    case USER          = 'user';
    case LOCALE        = 'locale';
}
