<?php

namespace Tests\Unit\Filters;

use App\Filters\LocaleFilter;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * @internal
 */
final class LocaleFilterTest extends CIUnitTestCase
{
    public function testUsesSessionLocaleWhenValid(): void
    {
        session()->set('locale', 'en');

        $filter = new LocaleFilter();
        $request = service('request');
        $request->setHeader('Accept-Language', 'es-MX,es;q=0.9');

        $filter->before($request);

        $this->assertSame('en', service('request')->getLocale());
        $this->assertSame('en', Services::language()->getLocale());
        $this->assertSame('en', session('locale'));
    }

    public function testNegotiatesAndPersistsLocaleFromAcceptLanguageHeader(): void
    {
        session()->remove('locale');

        $filter = new LocaleFilter();
        $request = service('request');
        $request->setHeader('Accept-Language', 'es-ES,es;q=0.9,en;q=0.8');

        $filter->before($request);

        $this->assertSame('es', service('request')->getLocale());
        $this->assertSame('es', Services::language()->getLocale());
        $this->assertSame('es', session('locale'));
    }

    public function testFallsBackToDefaultLocaleWhenHeaderIsMissing(): void
    {
        session()->remove('locale');

        $filter = new LocaleFilter();
        $request = service('request');
        $request->setHeader('Accept-Language', '');

        $filter->before($request);

        $defaultLocale = config('App')->defaultLocale;
        $this->assertSame($defaultLocale, service('request')->getLocale());
        $this->assertSame($defaultLocale, Services::language()->getLocale());
        $this->assertSame($defaultLocale, session('locale'));
    }

    public function testInvalidSessionLocaleFallsBackToHeaderNegotiation(): void
    {
        session()->set('locale', 'fr');

        $filter = new LocaleFilter();
        $request = service('request');
        $request->setHeader('Accept-Language', 'en-US,en;q=0.9');

        $filter->before($request);

        $this->assertSame('en', service('request')->getLocale());
        $this->assertSame('en', Services::language()->getLocale());
        $this->assertSame('en', session('locale'));
    }

    protected function tearDown(): void
    {
        session()->remove('locale');
        Services::reset();
        parent::tearDown();
    }
}
