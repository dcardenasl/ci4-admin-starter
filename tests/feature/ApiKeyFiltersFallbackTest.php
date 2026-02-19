<?php

namespace Tests\Feature;

use App\Services\ApiKeyApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class ApiKeyFiltersFallbackTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testIsActiveFilterIsForwardedToApiListQuery(): void
    {
        $mock = $this->createMock(ApiKeyApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return (($params['filter']['is_active'] ?? null) === '1')
                    && ! array_key_exists('is_active', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [
                        ['id' => 1, 'name' => 'Key A', 'key_prefix' => 'apk_123', 'is_active' => true],
                    ],
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('apiKeyApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/api-keys/data?is_active=1');

        $result->assertStatus(200);
        $this->assertStringContainsString('Key A', $result->getBody());
    }

    public function testSortIsForwardedWhenAllowed(): void
    {
        $mock = $this->createMock(ApiKeyApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return ($params['sort'] ?? null) === '-created_at';
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => []],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('apiKeyApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/api-keys/data?sort=-created_at');

        $result->assertStatus(200);
    }

    public function testInvalidSortIsIgnored(): void
    {
        $mock = $this->createMock(ApiKeyApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return ! array_key_exists('sort', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => []],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('apiKeyApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/api-keys/data?sort=unknown_field');

        $result->assertStatus(200);
    }

    public function testLimitAndPageAreForwardedToApiListQuery(): void
    {
        $mock = $this->createMock(ApiKeyApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return (int) ($params['limit'] ?? 0) === 50
                    && (int) ($params['page'] ?? 0) === 3
                    && ! array_key_exists('cursor', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data'         => [],
                    'current_page' => 3,
                    'last_page'    => 3,
                    'total'        => 100,
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('apiKeyApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/api-keys/data?limit=50&page=3');

        $result->assertStatus(200);
    }
}
