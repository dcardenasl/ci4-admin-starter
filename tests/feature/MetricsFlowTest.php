<?php

declare(strict_types=1);

namespace Tests\Feature;

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
                'data' => [['period' => '10:00', 'value' => 12, 'errors' => 0, 'latency' => 45]],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('metricsApiService', $metricsService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user' => ['permissions' => ['iam.admin-access']],
        ])->get('/admin/metrics');

        $result->assertStatus(200);
        $this->assertStringContainsString('321', $result->getBody());
        $this->assertStringContainsString('10:00', $result->getBody());
    }

    public function testMetricsPageFallsBackToDefaultPeriodWhenFilterIsInvalid(): void
    {
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

        Services::injectMock('metricsApiService', $metricsService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user' => ['permissions' => ['iam.admin-access']],
        ])->get('/admin/metrics?period=invalid');

        $result->assertStatus(200);
    }
}
