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

    public function testDashboardIndexReturnsPageShell(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 1, 'first_name' => 'Admin', 'permissions' => ['users.read']],
        ])->get('/dashboard');

        $result->assertStatus(200);
        $this->assertStringContainsString(lang('Dashboard.title'), $result->getBody());
        $this->assertStringContainsString('dashboard/widgets/stats', $result->getBody());
        $this->assertStringContainsString('dashboard/widgets/health', $result->getBody());
    }

    public function testWidgetStatsAggregatesAdminMetrics(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('list')
            ->with(['limit' => 1])
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['meta' => ['total' => 42]],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        $fileService = $this->createMock(FileApiService::class);
        $fileService->expects($this->once())
            ->method('list')
            ->with(['limit' => 5])
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['meta' => ['total' => 5], 'data' => []],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        $metricsService = $this->createMock(MetricsApiService::class);
        $metricsService->expects($this->once())
            ->method('summary')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['request_stats' => ['availability_percent' => 99.9]],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $userService);
        Services::injectMock('fileApiService', $fileService);
        Services::injectMock('metricsApiService', $metricsService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 1, 'first_name' => 'Admin', 'permissions' => ['users.read', 'metrics.read']],
        ])->get('/dashboard/widgets/stats');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('42', $body);
        $this->assertStringContainsString('99.9%', $body);
    }

    public function testWidgetStatsStillRendersWhenUserSummaryFails(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('list')
            ->willReturn([
                'ok'          => false,
                'status'      => 500,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['failed'],
                'fieldErrors' => [],
            ]);

        $fileService = $this->createMock(FileApiService::class);
        $fileService->expects($this->once())
            ->method('list')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['meta' => ['total' => 0], 'data' => []],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        $metricsService = $this->createMock(MetricsApiService::class);
        $metricsService->expects($this->once())
            ->method('summary')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $userService);
        Services::injectMock('fileApiService', $fileService);
        Services::injectMock('metricsApiService', $metricsService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 1, 'first_name' => 'Admin', 'permissions' => ['users.read']],
        ])->get('/dashboard/widgets/stats');

        $result->assertStatus(200);
    }

    public function testWidgetHealthReturnsHealthCard(): void
    {
        $healthService = $this->createMock(HealthApiServiceInterface::class);
        $healthService->expects($this->once())
            ->method('check')
            ->willReturn([
                'ok'         => true,
                'state'      => 'up',
                'status'     => 200,
                'path'       => '/health',
                'latency_ms' => 42,
                'data'       => ['state' => 'up'],
                'raw'        => '',
                'headers'    => [],
                'messages'   => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('healthApiService', $healthService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 1, 'first_name' => 'Admin', 'permissions' => []],
        ])->get('/dashboard/widgets/health');

        $result->assertStatus(200);
        $this->assertStringContainsString('42', $result->getBody());
    }

    public function testWidgetRecentFilesReturnsFileList(): void
    {
        $fileService = $this->createMock(FileApiService::class);
        $fileService->expects($this->once())
            ->method('list')
            ->with(['limit' => 5])
            ->willReturn([
                'ok'     => true,
                'status' => 200,
                'data'   => [
                    'meta' => ['total' => 1],
                    'data' => [
                        ['id' => 99, 'original_name' => 'report.pdf', 'category' => 'document', 'human_size' => '1 MB', 'uploaded_at' => '2026-01-01 00:00:00', 'is_image' => false],
                    ],
                ],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('fileApiService', $fileService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 1, 'first_name' => 'Admin', 'permissions' => []],
        ])->get('/dashboard/widgets/recent-files');

        $result->assertStatus(200);
        $this->assertStringContainsString('report.pdf', $result->getBody());
    }

    public function testWidgetActivityReturnsActivityList(): void
    {
        $metricsService = $this->createMock(MetricsApiService::class);
        $metricsService->expects($this->once())
            ->method('summary')
            ->willReturn([
                'ok'     => true,
                'status' => 200,
                'data'   => [
                    'recent_activity' => [
                        ['action' => 'login_success', 'user_email' => 'admin@example.com', 'entity_type' => 'session', 'created_at' => '2026-01-01 00:00:00'],
                    ],
                ],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('metricsApiService', $metricsService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 1, 'first_name' => 'Admin', 'permissions' => []],
        ])->get('/dashboard/widgets/activity');

        $result->assertStatus(200);
        $this->assertStringContainsString('login_success', $result->getBody());
    }
}
