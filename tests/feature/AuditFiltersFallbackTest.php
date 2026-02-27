<?php

namespace Tests\Feature;

use App\Services\AuditApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AuditFiltersFallbackTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testActionFilterIsForwardedToApiListQuery(): void
    {
        $mock = $this->createMock(AuditApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return (($params['filter']['action'] ?? null) === 'login')
                    && ! array_key_exists('action', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [
                        ['id' => 1, 'action' => 'login', 'userEmail' => 'user1@example.com', 'entityType' => 'user', 'createdAt' => '2026-02-01 10:00:00'],
                    ],
                    'meta' => [
                        'page'     => 1,
                        'lastPage' => 1,
                        'total'    => 1,
                    ],
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('auditApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/audit/data?action=login');

        $result->assertStatus(200);
        $this->assertStringContainsString('user1@example.com', $result->getBody());
    }

    public function testSortIsForwardedWhenAllowed(): void
    {
        $mock = $this->createMock(AuditApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return ($params['sort'] ?? null) === 'action';
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [],
                    'meta' => [
                        'page'     => 1,
                        'lastPage' => 1,
                        'total'    => 0,
                    ],
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('auditApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/audit/data?sort=action');

        $result->assertStatus(200);
    }

    public function testLimitAndPageAreForwardedToApiListQuery(): void
    {
        $mock = $this->createMock(AuditApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return (int) ($params['limit'] ?? 0) === 100
                    && (int) ($params['page'] ?? 0) === 2
                    && ! array_key_exists('cursor', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [],
                    'meta' => [
                        'page'     => 2,
                        'lastPage' => 3,
                        'total'    => 250,
                    ],
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('auditApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/audit/data?limit=100&page=2');

        $result->assertStatus(200);
    }
}
