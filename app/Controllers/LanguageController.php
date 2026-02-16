<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class LanguageController extends BaseController
{
    public function set(): RedirectResponse
    {
        $locale = $this->request->getGet('locale');
        $supported = config('App')->supportedLocales;

        if (is_string($locale) && in_array($locale, $supported, true)) {
            session()->set('locale', $locale);
        }

        return redirect()->back();
    }
}
