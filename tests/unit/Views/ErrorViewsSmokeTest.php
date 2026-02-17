<?php

namespace Tests\Unit\Views;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ErrorViewsSmokeTest extends CIUnitTestCase
{
    public function testError404ViewRendersInSpanish(): void
    {
        service('language')->setLocale('es');

        $html = view('errors/html/error_404', ['message' => 'missing page']);

        $this->assertStringContainsString('data-error-page="404"', $html);
        $this->assertStringContainsString(lang('App.error404Title'), $html);
    }

    public function testProductionErrorViewRendersInEnglish(): void
    {
        service('language')->setLocale('en');

        $html = view('errors/html/production');

        $this->assertStringContainsString('data-error-page="500"', $html);
        $this->assertStringContainsString(lang('App.error500Title'), $html);
    }
}
