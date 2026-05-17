<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Modules\Iam\Services\RoleApiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RoleApiServiceTest extends CIUnitTestCase
{
    public function testListCallsCorrectEndpoint(): void
    {
        $mock     = $this->createMock(ApiClientInterface::class);
        $expected = ['ok' => true, 'status' => 200, 'data' => []];

        $mock->expects($this->once())
            ->method('get')
            ->with('/api/v1/iam/roles', [])
            ->willReturn($expected);

        $service = new RoleApiService($mock);
        $this->assertSame($expected, $service->list());
    }

    public function testGetCallsCorrectEndpoint(): void
    {
        $mock     = $this->createMock(ApiClientInterface::class);
        $expected = ['ok' => true, 'status' => 200, 'data' => ['id' => 'uuid-1']];

        $mock->expects($this->once())
            ->method('get')
            ->with('/api/v1/iam/roles/uuid-1')
            ->willReturn($expected);

        $service = new RoleApiService($mock);
        $this->assertSame($expected, $service->get('uuid-1'));
    }

    public function testCreateCallsCorrectEndpoint(): void
    {
        $mock     = $this->createMock(ApiClientInterface::class);
        $payload  = ['name' => 'Test'];
        $expected = ['ok' => true, 'status' => 201, 'data' => ['id' => 'uuid-2']];

        $mock->expects($this->once())
            ->method('post')
            ->with('/api/v1/iam/roles', $payload)
            ->willReturn($expected);

        $service = new RoleApiService($mock);
        $this->assertSame($expected, $service->create($payload));
    }

    public function testDeleteCallsCorrectEndpoint(): void
    {
        $mock     = $this->createMock(ApiClientInterface::class);
        $expected = ['ok' => true, 'status' => 200, 'data' => []];

        $mock->expects($this->once())
            ->method('delete')
            ->with('/api/v1/iam/roles/uuid-3')
            ->willReturn($expected);

        $service = new RoleApiService($mock);
        $this->assertSame($expected, $service->delete('uuid-3'));
    }
}
