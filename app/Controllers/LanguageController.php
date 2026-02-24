<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class LanguageController extends BaseController
{
    public function set(): RedirectResponse
    {
        $locale = $this->request->getGet('locale');
        $supported = config('App')->supportedLocales;

        if (is_string($locale) && in_array($locale, $supported, true)) {
            session()->set('locale', $locale);
            service('request')->setLocale($locale);
            Services::language()->setLocale($locale);
        }

        return redirect()->back();
    }
}
