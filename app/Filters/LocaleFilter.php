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
        $config = config('App');
        $supported = $config->supportedLocales;
        $locale = session('locale');

        if (is_string($locale) && in_array($locale, $supported, true)) {
            $this->applyLocale($locale);

            return;
        }

        $locale = null;
        $acceptLanguage = $request->getHeaderLine('Accept-Language');

        if ($acceptLanguage !== '') {
            $locale = $this->parseAcceptLanguage($acceptLanguage, $supported);
        }

        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = $config->defaultLocale;
        }

        session()->set('locale', $locale);
        $this->applyLocale($locale);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}

    private function applyLocale(string $locale): void
    {
        service('request')->setLocale($locale);
        Services::language()->setLocale($locale);
    }

    /**
     * @param list<string> $supportedLocales
     */
    private function parseAcceptLanguage(string $acceptLanguage, array $supportedLocales): ?string
    {
        $languages = [];

        foreach (explode(',', $acceptLanguage) as $item) {
            $item = trim($item);

            if ($item === '') {
                continue;
            }

            if (preg_match('/^([a-zA-Z\-]+)(?:;q=([0-9.]+))?$/', $item, $matches) !== 1) {
                continue;
            }

            $lang = strtolower($matches[1]);
            $quality = isset($matches[2]) ? (float) $matches[2] : 1.0;
            $languages[$lang] = $quality;
        }

        arsort($languages);

        foreach (array_keys($languages) as $lang) {
            if (in_array($lang, $supportedLocales, true)) {
                return $lang;
            }

            $baseLang = explode('-', $lang)[0];
            if (in_array($baseLang, $supportedLocales, true)) {
                return $baseLang;
            }
        }

        return null;
    }
}
