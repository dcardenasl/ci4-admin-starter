<?php

namespace Tests\Feature;

use App\Services\ReportApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AdminReportsAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testReportsRouteRedirectsToLoginWhenNotAuthenticated(): void
    {
        $result = $this->get('/admin/reports');

        $result->assertRedirectTo(site_url('login'));
    }

    public function testReportsRouteRedirectsForNonAdminUsers(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'user'],
        ])->get('/admin/reports');

        $result->assertRedirectTo(site_url('dashboard'));
    }

    public function testAdminCanRenderReportsPage(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/reports');

        $result->assertStatus(200);
        $this->assertStringContainsString('Reportes', $result->getBody());
    }

    public function testAdminCanDownloadCsvReport(): void
    {
        $mock = $this->createMock(ReportApiService::class);
        $mock->method('exportCsv')->willReturn([
            'ok'          => true,
            'status'      => 200,
            'data'        => [
                'filename' => 'report.csv',
                'content'  => "id,email\n1,admin@example.com\n",
            ],
            'raw'         => '',
            'messages'    => [],
            'fieldErrors' => [],
        ]);

        Services::injectMock('reportApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/reports/export/csv');

        $result->assertStatus(200);
    }

    public function testReportFiltersAreForwardedToApiListQueryForDataEndpoint(): void
    {
        $mock = $this->createMock(ReportApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->with($this->callback(static function (array $params): bool {
                return (($params['filter']['role'] ?? null) === 'user')
                    && ! array_key_exists('role', $params)
                    && ($params['search'] ?? null) === 'john'
                    && ! array_key_exists('q', $params)
                    && ($params['sort'] ?? null) === '-created_at';
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [
                        ['id' => 2, 'email' => 'user@example.com', 'role' => 'user', 'status' => 'active', 'created_at' => '2026-02-01 11:00:00'],
                    ],
                    'current_page' => 1,
                    'last_page'    => 1,
                    'total'        => 1,
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('reportApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/reports/data?report_type=users&role=user&search=john&sort=-created_at');

        $result->assertStatus(200);
        $this->assertStringContainsString('user@example.com', $result->getBody());
    }
}
