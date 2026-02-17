<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ErrorPagesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withRoutes([
            ['GET', 'test-errors/not-found', static function () {
                return service('response')
                    ->setStatusCode(404)
                    ->setBody(view('errors/html/error_404', ['message' => 'Route not found']));
            }],
            ['GET', 'test-errors/server-error', static function () {
                return service('response')
                    ->setStatusCode(500)
                    ->setBody(view('errors/html/production'));
            }],
        ]);
    }

    public function testNotFoundPageUsesCustomTemplateForGuest(): void
    {
        $result = $this->get('/test-errors/not-found');
        $body = $result->getBody();

        $result->assertStatus(404);
        $this->assertStringContainsString('data-error-page="404"', $body);
        $this->assertStringContainsString(lang('App.error404Title'), $body);
        $this->assertStringContainsString(site_url('login'), $body);
    }

    public function testNotFoundPageShowsDashboardActionWhenSessionExists(): void
    {
        $result = $this->withSession(['access_token' => 'test-token'])->get('/test-errors/not-found');
        $body = $result->getBody();

        $result->assertStatus(404);
        $this->assertStringContainsString('data-error-page="404"', $body);
        $this->assertStringContainsString(site_url('dashboard'), $body);
    }

    public function testProductionErrorPageRendersWithExpectedStatus(): void
    {
        $result = $this->get('/test-errors/server-error');
        $body = $result->getBody();

        $result->assertStatus(500);
        $this->assertStringContainsString('data-error-page="500"', $body);
        $this->assertStringContainsString(lang('App.error500Title'), $body);
    }
}
