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
                return (($params['filter']['isActive'] ?? null) === '1')
                    && ! array_key_exists('isActive', $params);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [
                        ['id' => 1, 'name' => 'Key A', 'keyPrefix' => 'apk_123', 'isActive' => true],
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
        ])->get('/admin/api-keys/data?isActive=1');

        $result->assertStatus(200);
        $this->assertStringContainsString('Key A', $result->getBody());
    }

    public function testSortIsForwardedWhenAllowed(): void
    {
        $mock = $this->createMock(ApiKeyApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return ($params['sort'] ?? null) === '-createdAt';
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
        ])->get('/admin/api-keys/data?sort=-createdAt');

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
                    'data' => [],
                    'meta' => [
                        'page'     => 3,
                        'lastPage' => 3,
                        'total'    => 100,
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
        ])->get('/admin/api-keys/data?limit=50&page=3');

        $result->assertStatus(200);
    }
}
