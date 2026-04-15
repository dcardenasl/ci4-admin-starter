<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Modules\Users\Services\UserApiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UserApiServiceTest extends CIUnitTestCase
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

    public function testListReturnsUsers(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => [
                'data' => [
                    ['id' => 1, 'email' => 'john@example.com', 'first_name' => 'John'],
                    ['id' => 2, 'email' => 'jane@example.com', 'first_name' => 'Jane'],
                ],
            ],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $service = new UserApiService($this->createMockClient($expected));
        $result = $service->list(['page' => 1]);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertCount(2, $result['data']['data']);
    }

    public function testGetReturnsUserById(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => [
                'data' => [
                    'id'         => 123,
                    'email'      => 'user@example.com',
                    'first_name' => 'John',
                    'last_name'  => 'Doe',
                    'role'       => 'user',
                ],
            ],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $service = new UserApiService($this->createMockClient($expected));
        $result = $service->get('123');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('user@example.com', $result['data']['data']['email']);
    }

    public function testCreateUser(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 201,
            'data'        => ['data' => ['id' => 124, 'email' => 'newuser@example.com']],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('post')
            ->with('/users', [
                'first_name' => 'Jane',
                'last_name'  => 'Smith',
                'email'      => 'jane@example.com',
                'role'       => 'user',
            ])
            ->willReturn($expected);

        $service = new UserApiService($mock);
        $result = $service->create([
            'first_name' => 'Jane',
            'last_name'  => 'Smith',
            'email'      => 'jane@example.com',
            'role'       => 'user',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(201, $result['status']);
        $this->assertSame(124, $result['data']['data']['id']);
    }

    public function testUpdateUser(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => ['data' => ['id' => 123, 'role' => 'admin']],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('put')
            ->with('/users/123', ['role' => 'admin'])
            ->willReturn($expected);

        $service = new UserApiService($mock);
        $result = $service->update('123', ['role' => 'admin']);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('admin', $result['data']['data']['role']);
    }

    public function testDeleteUser(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 204,
            'data'        => [],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('delete')
            ->with('/users/123')
            ->willReturn($expected);

        $service = new UserApiService($mock);
        $result = $service->delete('123');

        $this->assertTrue($result['ok']);
        $this->assertSame(204, $result['status']);
    }

    public function testApproveUser(): void
    {
        $expected = [
            'ok'          => true,
            'status'      => 200,
            'data'        => ['data' => ['id' => 123, 'status' => 'approved']],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('post')
            ->with('/users/123/approve')
            ->willReturn($expected);

        $service = new UserApiService($mock);
        $result = $service->approve('123');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('approved', $result['data']['data']['status']);
    }

    public function testGetReturnsErrorWhenUserNotFound(): void
    {
        $expected = [
            'ok'          => false,
            'status'      => 404,
            'data'        => [],
            'raw'         => '',
            'messages'    => ['User not found'],
            'fieldErrors' => [],
        ];

        $service = new UserApiService($this->createMockClient($expected));
        $result = $service->get('999');

        $this->assertFalse($result['ok']);
        $this->assertSame(404, $result['status']);
    }
}
