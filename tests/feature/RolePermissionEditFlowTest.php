<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Auth\Services\AuthApiService;
use App\Modules\Iam\Services\PermissionApiService;
use App\Modules\Iam\Services\RoleApiService;
use App\Modules\Iam\Services\RoleMatrixApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Verifies that Roles create/edit manage only role metadata, while the
 * dedicated Roles x Permissions matrix owns permission assignment.
 *
 * @internal
 */
final class RolePermissionEditFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** @var array<string, list<string>> */
    private const ADMIN_SESSION = [
        'access_token' => 'token',
        'user'         => [
            'permissions' => [
                'users.read', 'users.write', 'audit.read', 'metrics.read',
                'apikeys.read', 'apikeys.write', 'iam.superadmin-access',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // IamLookups::applications() caches the catalogue cross-request, so
        // clear it to keep tests hermetic (otherwise prior runs/CI cold cache
        // can change behaviour).
        service('cache')->delete('iam_lookups_apps_v1');

        // RoleController::store()/update() call PermissionsSessionRefresher::forceRefresh()
        // after a successful save, which hits AuthApiService::me() over HTTP. Mock it so
        // these tests don't depend on a real hub server being reachable.
        $authService = $this->createMock(AuthApiService::class);
        $authService->method('me')->willReturn([
            'ok' => true, 'status' => 200,
            'data' => ['data' => self::ADMIN_SESSION['user']],
            'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);
        Services::injectMock('authApiService', $authService);
    }

    protected function tearDown(): void
    {
        Services::reset();
        service('cache')->delete('iam_lookups_apps_v1');
        parent::tearDown();
    }

    public function testStoreDoesNotRequirePermissionIds(): void
    {
        $roleMock = $this->createMock(RoleApiService::class);
        $roleMock->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['code'] ?? '') === 'editor'
                    && ($payload['name'] ?? '') === 'Editor'
                    && ! array_key_exists('permission_ids', $payload);
            }))
            ->willReturn([
                'ok' => true, 'status' => 201, 'data' => ['id' => 'uuid-new'],
                'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
            ]);

        Services::injectMock('roleApiService', $roleMock);

        $result = $this->withSession(self::ADMIN_SESSION)->post('/admin/iam/roles', [
            csrf_token()     => csrf_hash(),
            'code'           => 'editor',
            'name'           => 'Editor',
            'description'    => '',
        ]);

        $result->assertRedirectTo(site_url('admin/iam/roles'));
    }

    public function testUpdateDoesNotRequirePermissionIds(): void
    {
        $roleMock = $this->createMock(RoleApiService::class);
        $roleMock->expects($this->once())
            ->method('update')
            ->with('uuid-1', $this->callback(function (array $payload): bool {
                return ($payload['code'] ?? '') === 'editor'
                    && ($payload['name'] ?? '') === 'Editor'
                    && ! array_key_exists('permission_ids', $payload);
            }))
            ->willReturn([
                'ok' => true, 'status' => 200, 'data' => ['id' => 'uuid-1'],
                'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
            ]);

        Services::injectMock('roleApiService', $roleMock);

        $result = $this->withSession(self::ADMIN_SESSION)->post('/admin/iam/roles/uuid-1', [
            csrf_token()     => csrf_hash(),
            'code'           => 'editor',
            'name'           => 'Editor',
            'description'    => '',
        ]);

        $result->assertRedirectTo(site_url('admin/iam/roles'));
    }

    public function testUpdateOmitsPermissionIdsWhenFormDoesNotPostThem(): void
    {
        $roleMock = $this->createMock(RoleApiService::class);
        $roleMock->expects($this->once())
            ->method('update')
            ->with('uuid-2', $this->callback(function (array $payload): bool {
                // No permission_ids in payload → API leaves permissions untouched.
                return ! array_key_exists('permission_ids', $payload);
            }))
            ->willReturn([
                'ok' => true, 'status' => 200, 'data' => [],
                'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
            ]);

        Services::injectMock('roleApiService', $roleMock);

        $result = $this->withSession(self::ADMIN_SESSION)->post('/admin/iam/roles/uuid-2', [
            csrf_token()  => csrf_hash(),
            'code'        => 'editor',
            'name'        => 'Editor renamed',
            'description' => '',
        ]);

        $result->assertRedirectTo(site_url('admin/iam/roles'));
    }

    public function testEditViewLinksToDedicatedPermissionMatrix(): void
    {
        $roleMock = $this->createMock(RoleApiService::class);
        $roleMock->method('get')->with('uuid-3')->willReturn([
            'ok'          => true,
            'status'      => 200,
            'data'        => ['id' => 'uuid-3', 'code' => 'qa', 'name' => 'QA', 'description' => '', 'is_system' => false],
            'raw'         => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);

        Services::injectMock('roleApiService', $roleMock);

        $result = $this->withSession(self::ADMIN_SESSION)->get('/admin/iam/roles/uuid-3/edit');

        $result->assertStatus(200);
        $body = (string) $result->getBody();

        $this->assertStringNotContainsString('name="permission_ids[]"', $body);
        $this->assertStringContainsString('/admin/iam/role-permissions?tab=uuid-3', $body);
    }

    public function testShowPageIsReadOnly(): void
    {
        $roleMock = $this->createMock(RoleApiService::class);
        $roleMock->method('get')->willReturn([
            'ok'     => true,
            'status' => 200,
            'data'   => ['id' => 'uuid-4', 'code' => 'qa', 'name' => 'QA', 'description' => '', 'is_system' => false],
            'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);
        $roleMock->method('listPermissions')->willReturn([
            'ok' => true, 'status' => 200, 'data' => [],
            'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);

        $permMock = $this->createMock(PermissionApiService::class);
        $permMock->method('list')->willReturn([
            'ok' => true, 'status' => 200, 'data' => [],
            'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);

        Services::injectMock('roleApiService', $roleMock);
        Services::injectMock('permissionApiService', $permMock);

        $result = $this->withSession(self::ADMIN_SESSION)->get('/admin/iam/roles/uuid-4');

        $result->assertStatus(200);
        $body = (string) $result->getBody();

        $this->assertStringNotContainsString(
            'permissions/attach',
            $body,
            'show.php must not render the legacy attach form.'
        );
        $this->assertStringNotContainsString(
            'permissions/uuid-4/',
            $body,
            'show.php must not render any per-permission detach form.'
        );
    }

    public function testRolePermissionsMatrixRendersGroupedBulkControls(): void
    {
        $matrixMock = $this->createMock(RoleMatrixApiService::class);
        $matrixMock->method('matrix')->willReturn([
            'ok'     => true,
            'status' => 200,
            'data'   => [
                'roles' => [
                    ['id' => 99, 'code' => 'editor', 'name' => 'Editor', 'description' => '', 'is_system' => 0],
                ],
                'applications' => [
                    [
                        'id'          => 7,
                        'code'        => 'catalog',
                        'name'        => 'Catalog',
                        'permissions' => [
                            ['id' => 10, 'code' => 'catalog.product.read', 'resource' => 'product', 'action' => 'read', 'description' => 'Read products'],
                            ['id' => 11, 'code' => 'catalog.product.write', 'resource' => 'product', 'action' => 'write', 'description' => 'Write products'],
                        ],
                    ],
                ],
                'assignments' => [
                    99 => [10],
                ],
            ],
            'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);

        Services::injectMock('roleMatrixApiService', $matrixMock);

        $result = $this->withSession(self::ADMIN_SESSION)->get('/admin/iam/role-permissions?tab=99');

        $result->assertStatus(200);
        $body = (string) $result->getBody();

        $this->assertStringContainsString('data-role-id="99"', $body);
        $this->assertStringContainsString('data-app-id="7"', $body);
        $this->assertStringContainsString('data-resource="product"', $body);
        $this->assertStringContainsString('catalog.product.read', $body);
        $this->assertStringContainsString(lang('Iam.permissions_select_all'), $body);
        $this->assertStringContainsString(lang('Iam.permissions_clear_all'), $body);
        $this->assertStringContainsString(lang('Iam.permissions_select_group'), $body);
        $this->assertStringContainsString(lang('Iam.permissions_clear_group'), $body);
    }
}
