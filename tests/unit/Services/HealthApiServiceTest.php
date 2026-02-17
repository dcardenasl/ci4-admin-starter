<?php

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Services\HealthApiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HealthApiServiceTest extends CIUnitTestCase
{
    public function testCheckReturnsUpWhenAnyPathSucceeds(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                ['ok' => false, 'status' => 404, 'messages' => ['Not found']],
                ['ok' => true, 'status' => 200, 'messages' => []],
            );

        $service = new HealthApiService($mock, ['/health', '/status']);
        $result = $service->check();

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('/status', $result['path']);
        $this->assertGreaterThanOrEqual(0, $result['latency_ms']);
    }

    public function testCheckReturnsDownWhenAllPathsFail(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                ['ok' => false, 'status' => 503, 'messages' => ['Service unavailable']],
                ['ok' => false, 'status' => 500, 'messages' => ['Server error']],
            );

        $service = new HealthApiService($mock, ['/health', '/status']);
        $result = $service->check();

        $this->assertFalse($result['ok']);
        $this->assertSame('degraded', $result['state']);
        $this->assertSame(503, $result['status']);
        $this->assertSame('/health', $result['path']);
    }

    public function testCheckReturnsDownWhenApiIsUnreachable(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                ['ok' => false, 'status' => 0, 'messages' => ['Connection refused']],
                ['ok' => false, 'status' => 0, 'messages' => ['Connection refused']],
            );

        $service = new HealthApiService($mock, ['/health', '/status']);
        $result = $service->check();

        $this->assertFalse($result['ok']);
        $this->assertSame('down', $result['state']);
        $this->assertSame(0, $result['status']);
    }
}
