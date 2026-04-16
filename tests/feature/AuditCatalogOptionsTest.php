<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CatalogApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AuditCatalogOptionsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testAuditIndexRendersActionsFromCatalogFacets(): void
    {
        $mock = $this->createMock(CatalogApiService::class);
        $mock->expects($this->once())
            ->method('index')
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => [
                    'pagination' => [
                        'limit_options' => [10, 25, 50],
                    ],
                ],
                'raw' => '',
                'messages' => [],
                'fieldErrors' => [],
            ]);

        $mock->expects($this->once())
            ->method('auditFacets')
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => [
                    'actions' => [
                        ['value' => 'login_success', 'count' => 3],
                        ['value' => 'user_approved', 'count' => 1],
                    ],
                ],
                'raw' => '',
                'messages' => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('catalogApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user' => ['role' => 'admin'],
        ])->get('/admin/audit');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('value="login_success"', $body);
        $this->assertStringContainsString('value="user_approved"', $body);
        $this->assertStringContainsString('login_success (3)', $body);
    }
}
