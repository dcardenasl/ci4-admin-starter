<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Dashboard\Services\HealthApiServiceInterface;
use App\Modules\Files\Services\FileApiService;
use App\Modules\Metrics\Services\MetricsApiService;
use App\Modules\Users\Services\UserApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class DashboardFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testDashboardAggregatesAdminMetrics(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('list')
            ->with(['limit' => 1])
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => ['meta' => ['total' => 42]],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        $fileService = $this->createMock(FileApiService::class);
        $fileService->expects($this->once())
            ->method('list')
            ->with(['limit' => 5])
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => [
                    'meta' => ['total' => 5],
                    'data' => [
                        ['id' => 1, 'original_name' => 'report.pdf', 'mime_type' => 'application/pdf', 'human_size' => '1 MB', 'uploaded_at' => '2026-04-13 10:00:00'],
                    ],
                ],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        $metricsService = $this->createMock(MetricsApiService::class);
        $metricsService->expects($this->once())
            ->method('summary')
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => [
                    'request_stats' => ['availability_percent' => 99.9],
                    'recent_activity' => [
                        ['action' => 'login_success', 'created_at' => '2026-04-13 11:00:00'],
                    ],
                ],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        $healthService = $this->createMock(HealthApiServiceInterface::class);
        $healthService->expects($this->once())
            ->method('check')
            ->willReturn([
                'ok'         => true,
                'state'      => 'up',
                'status'     => 200,
                'path'       => '/health',
                'latency_ms' => 87,
                'message'    => 'API available',
            ]);

        Services::injectMock('userApiService', $userService);
        Services::injectMock('fileApiService', $fileService);
        Services::injectMock('metricsApiService', $metricsService);
        Services::injectMock('healthApiService', $healthService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user' => ['id' => 1, 'first_name' => 'Admin', 'role' => 'admin'],
        ])->get('/dashboard');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('42', $body);
        $this->assertStringContainsString('99.9%', $body);
        $this->assertStringContainsString('login_success', $body);
        $this->assertStringContainsString(lang('Dashboard.title'), $body);
    }

    public function testDashboardStillRendersWhenUserSummaryFails(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('list')
            ->willReturn([
                'ok' => false,
                'status' => 500,
                'data' => [],
                'raw' => '',
                'headers' => [],
                'messages' => ['failed'],
                'fieldErrors' => [],
            ]);

        $fileService = $this->createMock(FileApiService::class);
        $fileService->expects($this->once())
            ->method('list')
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => ['meta' => ['total' => 0], 'data' => []],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        $metricsService = $this->createMock(MetricsApiService::class);
        $metricsService->expects($this->once())
            ->method('summary')
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => ['recent_activity' => []],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        $healthService = $this->createMock(HealthApiServiceInterface::class);
        $healthService->expects($this->once())
            ->method('check')
            ->willReturn([
                'ok'         => false,
                'state'      => 'down',
                'status'     => 0,
                'path'       => '/health',
                'latency_ms' => 0,
                'message'    => 'API unavailable',
            ]);

        Services::injectMock('userApiService', $userService);
        Services::injectMock('fileApiService', $fileService);
        Services::injectMock('metricsApiService', $metricsService);
        Services::injectMock('healthApiService', $healthService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user' => ['id' => 1, 'first_name' => 'Admin', 'role' => 'admin'],
        ])->get('/dashboard');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString(lang('Dashboard.title'), $body);
    }
}
