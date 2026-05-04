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
        session()->set('user', ['permissions' => ['users.read', 'users.write', 'audit.read', 'metrics.read', 'apikeys.read', 'apikeys.write', 'iam.superadmin-access']]);

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

    public function testFilterReadsAllowedPermissionsFromConfig(): void
    {
        // Override config — the filter must pick up the change without code edits.
        config('AdminAccess')->permissions = ['custom.module.read'];

        $request = service('request');
        $request->removeHeader('X-Requested-With'); // ensure non-AJAX path

        session()->set('user', ['permissions' => ['custom.module.read']]);
        $filter = new AdminFilter();
        $this->assertNull($filter->before($request));

        session()->set('user', ['permissions' => ['users.read']]); // would have passed before
        $this->assertInstanceOf(RedirectResponse::class, $filter->before($request));
    }
}
