<?php

namespace Tests\Feature;

use App\Services\UserApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class UserFiltersFallbackTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testRoleFilterIsForwardedToApiListQuery(): void
    {
        $mock = $this->createMock(UserApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return (($params['filter']['role'] ?? null) === 'user')
                    && ! array_key_exists('role', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data'         => [
                        ['id' => 2, 'first_name' => 'User', 'last_name' => 'One', 'email' => 'user@example.com', 'role' => 'user', 'status' => 'active'],
                    ],
                    'current_page' => 1,
                    'last_page'    => 1,
                    'total'        => 1,
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/users/data?role=user');

        $result->assertStatus(200);
        $this->assertStringContainsString('user@example.com', $result->getBody());
    }

    public function testSortIsForwardedWhenAllowed(): void
    {
        $mock = $this->createMock(UserApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return ($params['sort'] ?? null) === '-created_at';
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data'         => [],
                    'current_page' => 1,
                    'last_page'    => 1,
                    'total'        => 0,
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/users/data?sort=-created_at');

        $result->assertStatus(200);
    }

    public function testInvalidSortIsIgnored(): void
    {
        $mock = $this->createMock(UserApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return ! array_key_exists('sort', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data'         => [],
                    'current_page' => 1,
                    'last_page'    => 1,
                    'total'        => 0,
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/users/data?sort=unknown_field');

        $result->assertStatus(200);
    }
}
