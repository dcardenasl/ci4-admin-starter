<?php

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Services\ApiKeyApiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ApiKeyApiServiceTest extends CIUnitTestCase
{
    public function testListUsesApiKeysEndpoint(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);
        $expected = ['ok' => true, 'status' => 200, 'data' => []];

        $mock->expects($this->once())
            ->method('get')
            ->with('/api-keys', ['search' => 'mobile'])
            ->willReturn($expected);

        $service = new ApiKeyApiService($mock);
        $result = $service->list(['search' => 'mobile']);

        $this->assertSame($expected, $result);
    }

    public function testGetUsesApiKeysIdEndpoint(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);
        $expected = ['ok' => true, 'status' => 200, 'data' => ['id' => 7]];

        $mock->expects($this->once())
            ->method('get')
            ->with('/api-keys/7')
            ->willReturn($expected);

        $service = new ApiKeyApiService($mock);
        $result = $service->get(7);

        $this->assertSame($expected, $result);
    }

    public function testCreateUsesApiKeysEndpoint(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);
        $payload = ['name' => 'My App'];
        $expected = ['ok' => true, 'status' => 201, 'data' => ['id' => 3]];

        $mock->expects($this->once())
            ->method('post')
            ->with('/api-keys', $payload)
            ->willReturn($expected);

        $service = new ApiKeyApiService($mock);
        $result = $service->create($payload);

        $this->assertSame($expected, $result);
    }

    public function testUpdateUsesApiKeysIdEndpoint(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);
        $payload = ['name' => 'Updated'];
        $expected = ['ok' => true, 'status' => 200, 'data' => ['id' => 5]];

        $mock->expects($this->once())
            ->method('put')
            ->with('/api-keys/5', $payload)
            ->willReturn($expected);

        $service = new ApiKeyApiService($mock);
        $result = $service->update(5, $payload);

        $this->assertSame($expected, $result);
    }

    public function testDeleteUsesApiKeysIdEndpoint(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);
        $expected = ['ok' => true, 'status' => 200, 'data' => []];

        $mock->expects($this->once())
            ->method('delete')
            ->with('/api-keys/11')
            ->willReturn($expected);

        $service = new ApiKeyApiService($mock);
        $result = $service->delete(11);

        $this->assertSame($expected, $result);
    }
}
