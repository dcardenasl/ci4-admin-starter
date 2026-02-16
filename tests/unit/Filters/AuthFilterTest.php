<?php

namespace Tests\Unit\Filters;

use App\Filters\AuthFilter;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthFilterTest extends CIUnitTestCase
{
    public function testRedirectsWhenNoToken(): void
    {
        session()->remove('access_token');

        $filter = new AuthFilter();
        $request = service('request');
        $result = $filter->before($request);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testAllowsWhenTokenPresent(): void
    {
        session()->set('access_token', 'test-token-value');

        $filter = new AuthFilter();
        $request = service('request');
        $result = $filter->before($request);

        $this->assertNull($result);

        session()->remove('access_token');
    }
}
