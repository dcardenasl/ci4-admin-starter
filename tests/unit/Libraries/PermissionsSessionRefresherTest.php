<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Libraries\PermissionsSessionRefresher;
use App\Modules\Auth\Services\AuthApiServiceInterface;
use App\Support\SessionKeys;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PermissionsSessionRefresherTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        session()->remove('permissions_refreshed_at');
        session()->remove(SessionKeys::USER->value);
        parent::tearDown();
    }

    public function testRefreshIfStaleSkipsFreshSession(): void
    {
        session()->set('permissions_refreshed_at', time());
        $auth = $this->createMock(AuthApiServiceInterface::class);
        $auth->expects($this->never())->method('me');

        (new PermissionsSessionRefresher($auth))->refreshIfStale(60);
    }

    public function testForceRefreshUpdatesSessionUserFromAuthMe(): void
    {
        $auth = $this->createMock(AuthApiServiceInterface::class);
        $auth->expects($this->once())
            ->method('me')
            ->willReturn([
                'ok' => true,
                'data' => ['data' => ['id' => 5, 'permissions' => ['cms.pages.read']]],
            ]);

        (new PermissionsSessionRefresher($auth))->forceRefresh();

        $this->assertSame(['cms.pages.read'], session('user.permissions'));
    }
}
