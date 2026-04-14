<?php

namespace Tests\Feature;

use App\Modules\Audit\Services\AuditApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AuditFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testAuditShowRendersEntryDetails(): void
    {
        $auditService = $this->createMock(AuditApiService::class);
        $auditService->expects($this->once())
            ->method('get')
            ->with('77')
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => [
                    'data' => [
                        'id' => 77,
                        'user_email' => 'admin@example.com',
                        'action' => 'delete',
                        'entity_type' => 'user',
                        'entity_id' => 15,
                    ],
                ],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('auditApiService', $auditService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user' => ['role' => 'admin'],
        ])->get('/admin/audit/77');

        $result->assertStatus(200);
        $this->assertStringContainsString('admin@example.com', $result->getBody());
        $this->assertStringContainsString('#15', $result->getBody());
    }

    public function testAuditByEntityRedirectsToSearch(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user' => ['role' => 'admin'],
        ])->get('/admin/audit/entity/user/15');

        $result->assertRedirectTo(site_url('admin/audit?search=user%2015'));
    }
}
