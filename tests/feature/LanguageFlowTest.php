<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class LanguageFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testSetLocalePersistsSupportedLocaleInSession(): void
    {
        $result = $this->withHeaders([
            'Referer' => site_url('login'),
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/language/set', ['locale' => 'en']);

        $result->assertRedirect();
        $result->assertSessionHas('locale', 'en');
    }

    public function testUnsupportedLocaleDoesNotOverwriteSession(): void
    {
        $result = $this->withSession([
            'locale' => 'es',
        ])->withHeaders([
            'Referer' => site_url('login'),
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/language/set', ['locale' => 'fr']);

        $result->assertRedirect();
        $result->assertSessionHas('locale', 'es');
    }
}
