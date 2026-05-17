<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Iam\Services\RoleApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class RoleFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testAdminRoutesRequireAuth(): void
    {
        $result = $this->get('/admin/iam/roles');
        $result->assertRedirectTo(site_url('login'));
    }

    public function testNonAdminCannotAccess(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => []],
        ])->get('/admin/iam/roles');

        $result->assertRedirectTo(site_url('dashboard'));
    }

    public function testIndexRendersForAdmin(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['users.read', 'users.write', 'audit.read', 'metrics.read', 'apikeys.read', 'apikeys.write', 'iam.superadmin-access', 'iam.superadmin-access']],
        ])->get('/admin/iam/roles');

        $result->assertStatus(200);
    }

    public function testStoreValidationFailureRedirectsBack(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['users.read', 'users.write', 'audit.read', 'metrics.read', 'apikeys.read', 'apikeys.write', 'iam.superadmin-access', 'iam.superadmin-access']],
        ])->post('/admin/iam/roles', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
    }

    public function testDeleteSuccessRedirectsToList(): void
    {
        $mock = $this->createMock(RoleApiService::class);
        $mock->expects($this->once())
            ->method('delete')
            ->with('test-uuid')
            ->willReturn([
                'ok' => true, 'status' => 200, 'data' => [],
                'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
            ]);

        Services::injectMock('roleApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['users.read', 'users.write', 'audit.read', 'metrics.read', 'apikeys.read', 'apikeys.write', 'iam.superadmin-access', 'iam.superadmin-access']],
        ])->post('/admin/iam/roles/test-uuid/delete', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('admin/iam/roles'));
    }
}
