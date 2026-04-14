<?php

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Services\AuditApiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuditApiServiceTest extends CIUnitTestCase
{
    private function createMockClient(array $returnValue): ApiClientInterface
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->method('get')->willReturn($returnValue);
        $mock->method('post')->willReturn($returnValue);
        $mock->method('put')->willReturn($returnValue);
        $mock->method('delete')->willReturn($returnValue);

        return $mock;
    }

    public function testListReturnsAuditLogs(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => [
                'data' => [
                    [
                        'id'        => 1,
                        'action'    => 'create',
                        'entity'    => 'user',
                        'result'    => 'success',
                        'user_id'   => 1,
                        'timestamp' => '2024-01-01T10:00:00Z',
                    ],
                    [
                        'id'        => 2,
                        'action'    => 'update',
                        'entity'    => 'file',
                        'result'    => 'success',
                        'user_id'   => 1,
                        'timestamp' => '2024-01-01T11:00:00Z',
                    ],
                ],
            ],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $service = new AuditApiService($this->createMockClient($expected));
        $result = $service->list();

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertCount(2, $result['data']['data']);
    }

    public function testListWithFilters(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => [
                'data' => [
                    [
                        'id'        => 1,
                        'action'    => 'create',
                        'entity'    => 'user',
                        'result'    => 'success',
                        'user_id'   => 5,
                        'timestamp' => '2024-01-01T10:00:00Z',
                    ],
                ],
            ],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('get')
            ->with('/audit', ['user_id' => 5, 'page' => 1])
            ->willReturn($expected);

        $service = new AuditApiService($mock);
        $result = $service->list(['user_id' => 5, 'page' => 1]);

        $this->assertTrue($result['ok']);
        $this->assertCount(1, $result['data']['data']);
    }

    public function testGetReturnsAuditLogById(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => [
                'data' => [
                    'id'        => 123,
                    'action'    => 'delete',
                    'entity'    => 'api_key',
                    'result'    => 'success',
                    'user_id'   => 1,
                    'timestamp' => '2024-01-01T12:00:00Z',
                    'details'   => ['key_id' => 'apk_xxx'],
                ],
            ],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $service = new AuditApiService($this->createMockClient($expected));
        $result = $service->get('123');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('delete', $result['data']['data']['action']);
    }

    public function testByEntityReturnsLogsForEntity(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => [
                'data' => [
                    ['id' => 1, 'action' => 'create', 'entity' => 'user'],
                    ['id' => 2, 'action' => 'update', 'entity' => 'user'],
                    ['id' => 3, 'action' => 'delete', 'entity' => 'user'],
                ],
            ],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('get')
            ->with('/audit/entity/user/456')
            ->willReturn($expected);

        $service = new AuditApiService($mock);
        $result = $service->byEntity('user', '456');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertCount(3, $result['data']['data']);
    }

    public function testGetReturnsErrorWhenNotFound(): void
    {
        $expected = [
            'ok'          => false,
            'status'      => 404,
            'data'        => [],
            'raw'         => '',
            'messages'    => ['Audit log not found'],
            'fieldErrors' => [],
        ];

        $service = new AuditApiService($this->createMockClient($expected));
        $result = $service->get('999');

        $this->assertFalse($result['ok']);
        $this->assertSame(404, $result['status']);
    }
}
