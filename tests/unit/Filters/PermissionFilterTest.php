<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\PermissionFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Audit B9.3 (2026-05-07): pin behavior of `PermissionFilter` so the
 * admin gating contract doesn't drift. The filter:
 *  - Lets the request through when the session user holds the required
 *    permission.
 *  - Redirects to /dashboard with an error flash for browser requests
 *    when the permission is missing.
 *  - Returns JSON 403 for AJAX requests.
 *  - Treats an empty/missing required argument as a deny.
 *
 * @internal
 */
final class PermissionFilterTest extends CIUnitTestCase
{
    private PermissionFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new PermissionFilter();
        session()->set('user', null);
    }

    protected function tearDown(): void
    {
        session()->remove(['user']);
        parent::tearDown();
    }

    private function requestWithAjax(bool $isAjax): IncomingRequest
    {
        $request = $this->getMockBuilder(IncomingRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAJAX'])
            ->getMock();
        $request->method('isAJAX')->willReturn($isAjax);

        return $request;
    }

    public function testAllowsWhenSessionUserHasRequiredPermission(): void
    {
        session()->set('user', ['permissions' => ['users.read', 'users.write']]);

        $result = $this->filter->before($this->requestWithAjax(false), ['users.read']);

        $this->assertNull($result, 'Filter must return null (= continue) when permission is held.');
    }

    public function testRedirectsBrowserRequestsToDashboardWhenPermissionMissing(): void
    {
        session()->set('user', ['permissions' => ['users.read']]);

        $result = $this->filter->before($this->requestWithAjax(false), ['users.write']);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testReturnsJsonForbiddenForAjaxRequestsWhenPermissionMissing(): void
    {
        session()->set('user', ['permissions' => []]);

        $result = $this->filter->before($this->requestWithAjax(true), ['users.write']);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(403, $result->getStatusCode());
        $this->assertStringContainsString(
            'application/json',
            $result->getHeaderLine('Content-Type')
        );
    }

    public function testEmptyArgumentsDenyByDefault(): void
    {
        // Holding the permission is irrelevant when the route omits it.
        session()->set('user', ['permissions' => ['users.write']]);

        $result = $this->filter->before($this->requestWithAjax(false), []);

        $this->assertInstanceOf(
            RedirectResponse::class,
            $result,
            'Empty $arguments must fail closed — never let an unconfigured route through.'
        );
    }

    public function testAfterIsNoop(): void
    {
        $request = $this->requestWithAjax(false);
        $response = service('response');

        $result = $this->filter->after($request, $response);

        $this->assertNull($result);
    }
}
