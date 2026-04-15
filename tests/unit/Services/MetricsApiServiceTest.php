<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Modules\Metrics\Services\MetricsApiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MetricsApiServiceTest extends CIUnitTestCase
{
    public function testSummaryUsesMetricsEndpoint(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);
        $expected = ['ok' => true, 'status' => 200, 'data' => ['total_users' => 20]];
        $filters = ['filter' => ['date_from' => '2026-01-01']];

        $mock->expects($this->once())
            ->method('get')
            ->with('/metrics', $filters)
            ->willReturn($expected);

        $service = new MetricsApiService($mock);
        $result = $service->summary($filters);

        $this->assertSame($expected, $result);
    }

    public function testTimeseriesReturnsErrorWhenEndpointFails(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->expects($this->once())
            ->method('get')
            ->with('/metrics/timeseries', ['group_by' => 'day'])
            ->willReturn(['ok' => false, 'status' => 404, 'data' => [], 'messages' => ['Not found']]);

        $service = new MetricsApiService($mock);
        $result = $service->timeseries(['group_by' => 'day']);

        $this->assertFalse($result['ok']);
        $this->assertSame(404, $result['status']);
    }

    public function testTimeseriesTransformsParallelArraysToPointObjects(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->expects($this->once())
            ->method('get')
            ->with('/metrics/timeseries', ['period' => '24h'])
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => [
                    'dates' => ['2026-01-01', '2026-01-02'],
                    'requests' => [100, 200],
                    'errors' => [2, 5],
                    'latency' => [45, 50],
                ],
            ]);

        $service = new MetricsApiService($mock);
        $result = $service->timeseries(['period' => '24h']);

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['data']);
        $this->assertSame('2026-01-01', $result['data'][0]['period']);
        $this->assertSame(100, $result['data'][0]['value']);
        $this->assertSame(2, $result['data'][0]['errors']);
        $this->assertSame(45, $result['data'][0]['latency']);
    }
}
