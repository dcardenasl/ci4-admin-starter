<?php

namespace Tests\Feature;

use App\Services\CatalogApiService;
use App\Modules\Metrics\Services\MetricsApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class MetricsFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testMetricsPageRendersSummaryAndTimeseries(): void
    {
        $catalogService = $this->createMock(CatalogApiService::class);
        $catalogService->expects($this->once())
            ->method('index')
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => ['metrics' => ['periods' => ['1h', '24h', '7d']]],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        $metricsService = $this->createMock(MetricsApiService::class);
        $metricsService->expects($this->once())
            ->method('summary')
            ->with(['period' => '24h'])
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => ['request_stats' => ['total_requests' => 321, 'avg_response_time_ms' => 87, 'availability_percent' => 99.2, 'successful_requests' => 315]],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);
        $metricsService->expects($this->once())
            ->method('timeseries')
            ->with(['period' => '24h'])
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => ['timeseries' => [['period' => '10:00', 'value' => 12]]],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('catalogApiService', $catalogService);
        Services::injectMock('metricsApiService', $metricsService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user' => ['role' => 'admin'],
        ])->get('/admin/metrics');

        $result->assertStatus(200);
        $this->assertStringContainsString('321', $result->getBody());
        $this->assertStringContainsString('10:00', $result->getBody());
    }

    public function testMetricsPageFallsBackToDefaultPeriodWhenFilterIsInvalid(): void
    {
        $catalogService = $this->createMock(CatalogApiService::class);
        $catalogService->method('index')->willReturn([
            'ok' => true,
            'status' => 200,
            'data' => ['metrics' => ['periods' => ['24h']]],
            'raw' => '',
            'headers' => [],
            'messages' => [],
            'fieldErrors' => [],
        ]);

        $metricsService = $this->createMock(MetricsApiService::class);
        $metricsService->expects($this->once())
            ->method('summary')
            ->with(['period' => '24h'])
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => [],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);
        $metricsService->expects($this->once())
            ->method('timeseries')
            ->with(['period' => '24h'])
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => [],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('catalogApiService', $catalogService);
        Services::injectMock('metricsApiService', $metricsService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user' => ['role' => 'admin'],
        ])->get('/admin/metrics?period=invalid');

        $result->assertStatus(200);
    }
}
