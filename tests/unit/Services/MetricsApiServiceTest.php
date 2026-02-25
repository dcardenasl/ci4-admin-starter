<?php

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Services\MetricsApiService;
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

    public function testTimeseriesFallsBackToMetricsWhenSpecificEndpointFails(): void
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->expects($this->exactly(2))
            ->method('get')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                ['ok' => false, 'status' => 404, 'data' => []],
                ['ok' => true, 'status' => 200, 'data' => ['timeseries' => [['period' => '2026-01-01', 'value' => 3]]]],
            );

        $service = new MetricsApiService($mock);
        $result = $service->timeseries(['group_by' => 'day']);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
    }
}
