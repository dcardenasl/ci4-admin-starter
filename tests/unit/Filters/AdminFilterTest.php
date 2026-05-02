<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\AdminFilter;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AdminFilterTest extends CIUnitTestCase
{
    public function testAllowsUserWithIamAdminAccessPermission(): void
    {
        session()->set('user', ['permissions' => ['iam.admin-access']]);

        $filter = new AdminFilter();
        $result = $filter->before(service('request'));

        $this->assertNull($result);
    }

    public function testRedirectsUserWithoutIamAdminAccess(): void
    {
        session()->set('user', ['permissions' => ['files.read']]);

        $filter = new AdminFilter();
        $result = $filter->before(service('request'));

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testRedirectsUserWithMissingPermissionsKey(): void
    {
        session()->set('user', ['email' => 'someone@example.com']);

        $filter = new AdminFilter();
        $result = $filter->before(service('request'));

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testAjaxRequestGetsForbiddenJson(): void
    {
        session()->set('user', ['permissions' => []]);

        $request = service('request');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $filter = new AdminFilter();
        $result = $filter->before($request);

        $this->assertSame(403, $result?->getStatusCode());
        $this->assertStringContainsString('permis', strtolower((string) $result?->getBody()));
    }
}
