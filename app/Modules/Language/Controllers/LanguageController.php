<?php

declare(strict_types=1);

namespace App\Modules\Language\Controllers;

use App\Controllers\BaseWebController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class LanguageController extends BaseWebController
{
    public function set(): RedirectResponse
    {
        $locale = $this->request->getPost('locale');
        $supported = config('App')->supportedLocales;

        if (is_string($locale) && in_array($locale, $supported, true)) {
            session()->set('locale', $locale);
            service('request')->setLocale($locale);
            Services::language()->setLocale($locale);
        }

        return redirect()->back();
    }
}
