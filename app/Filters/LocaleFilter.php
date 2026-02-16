<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class LocaleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $supported = config('App')->supportedLocales;
        $locale = session('locale');

        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = config('App')->defaultLocale;
        }

        Services::language()->setLocale($locale);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
