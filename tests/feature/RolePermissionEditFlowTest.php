<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Iam\Services\ApplicationApiService;
use App\Modules\Iam\Services\PermissionApiService;
use App\Modules\Iam\Services\RoleApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Verifies the inline-permission-editor UX on Roles create/edit, mirroring the
 * Users → role_ids[] pattern. The role-detail (show) page must be read-only:
 * no attach/detach forms.
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
    }

    protected function tearDown(): void
    {
        Services::reset();
        service('cache')->delete('iam_lookups_apps_v1');
        parent::tearDown();
    }

    public function testStoreForwardsPermissionIdsAtomically(): void
    {
        $roleMock = $this->createMock(RoleApiService::class);
        $roleMock->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['code'] ?? '') === 'editor'
                    && ($payload['name'] ?? '') === 'Editor'
                    && ($payload['permission_ids'] ?? null) === [10, 20];
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
            'permission_ids' => ['10', '20', '', '0', 'abc'],
        ]);

        $result->assertRedirectTo(site_url('admin/iam/roles'));
    }

    public function testUpdateForwardsPermissionIdsAtomically(): void
    {
        $roleMock = $this->createMock(RoleApiService::class);
        $roleMock->expects($this->once())
            ->method('update')
            ->with('uuid-1', $this->callback(function (array $payload): bool {
                return array_key_exists('permission_ids', $payload)
                    && $payload['permission_ids'] === [5, 7];
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
            'permission_ids' => ['5', '7'],
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

    public function testEditViewPreMarksAssignedPermissions(): void
    {
        $roleMock = $this->createMock(RoleApiService::class);
        $roleMock->method('get')->with('uuid-3')->willReturn([
            'ok'          => true,
            'status'      => 200,
            'data'        => ['id' => 'uuid-3', 'code' => 'qa', 'name' => 'QA', 'description' => '', 'is_system' => false],
            'raw'         => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);
        $roleMock->method('listPermissions')->with('uuid-3')->willReturn([
            'ok'     => true,
            'status' => 200,
            'data'   => [
                ['id' => 11, 'code' => 'users.read', 'description' => ''],
                ['id' => 22, 'code' => 'users.write', 'description' => ''],
            ],
            'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);

        $permMock = $this->createMock(PermissionApiService::class);
        $permMock->method('list')->willReturn([
            'ok'     => true,
            'status' => 200,
            'data'   => [
                ['id' => 11, 'code' => 'users.read', 'description' => ''],
                ['id' => 22, 'code' => 'users.write', 'description' => ''],
                ['id' => 33, 'code' => 'audit.read', 'description' => ''],
            ],
            'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);

        $appMock = $this->createMock(ApplicationApiService::class);
        $appMock->method('list')->willReturn([
            'ok' => true, 'status' => 200,
            'data' => ['data' => [], 'meta' => ['total' => 0, 'per_page' => 100]],
            'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
        ]);

        Services::injectMock('roleApiService', $roleMock);
        Services::injectMock('permissionApiService', $permMock);
        Services::injectMock('applicationApiService', $appMock);

        $result = $this->withSession(self::ADMIN_SESSION)->get('/admin/iam/roles/uuid-3/edit');

        $result->assertStatus(200);
        $body = (string) $result->getBody();

        // Assigned ids are pre-checked.
        $this->assertMatchesRegularExpression(
            '/<input type="checkbox" name="permission_ids\[\]" value="11"\s+checked/',
            $body,
            'Assigned permission #11 should be pre-checked.'
        );
        $this->assertMatchesRegularExpression(
            '/<input type="checkbox" name="permission_ids\[\]" value="22"\s+checked/',
            $body,
            'Assigned permission #22 should be pre-checked.'
        );
        // Unassigned permission #33 must be present (the form lists it) but
        // not pre-checked. The simplest invariant: there must NOT be a
        // 'value="33"\s+checked' substring on the page.
        $this->assertDoesNotMatchRegularExpression(
            '/value="33"\s+checked/',
            $body,
            'Unassigned permission #33 must render unchecked.'
        );
        $this->assertStringContainsString('value="33"', $body, 'Permission #33 must be listed.');
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
}
