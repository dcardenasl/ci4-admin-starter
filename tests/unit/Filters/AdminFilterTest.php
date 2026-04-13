<?php

namespace Tests\Unit\Filters;

use App\Filters\AdminFilter;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AdminFilterTest extends CIUnitTestCase
{
    public function testAllowsAdminUser(): void
    {
        session()->set('user', ['role' => 'admin']);

        $filter = new AdminFilter();
        $result = $filter->before(service('request'));

        $this->assertNull($result);
    }

    public function testRedirectsNonAdminUser(): void
    {
        session()->set('user', ['role' => 'user']);

        $filter = new AdminFilter();
        $result = $filter->before(service('request'));

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testAjaxRequestGetsForbiddenJson(): void
    {
        session()->set('user', ['role' => 'user']);

        $request = service('request');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $filter = new AdminFilter();
        $result = $filter->before($request);

        $this->assertSame(403, $result?->getStatusCode());
        $this->assertStringContainsString('permis', strtolower((string) $result?->getBody()));
    }
}
