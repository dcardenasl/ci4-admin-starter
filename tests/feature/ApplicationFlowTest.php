<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Iam\Services\ApplicationApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class ApplicationFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testIndexRequiresAuth(): void
    {
        $result = $this->get('/admin/iam/applications');
        $result->assertRedirectTo(site_url('login'));
    }

    public function testNonSuperadminCannotAccess(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['users.read']],
        ])->get('/admin/iam/applications');

        $result->assertRedirectTo(site_url('dashboard'));
    }

    public function testIndexRendersForSuperadmin(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['iam.superadmin-access']],
        ])->get('/admin/iam/applications');

        $result->assertStatus(200);
    }

    public function testShowSurfacesApiPayloadFields(): void
    {
        $mock = $this->createMock(ApplicationApiService::class);
        $mock->expects($this->once())
            ->method('get')
            ->with('1')
            ->willReturn([
                'ok' => true, 'status' => 200,
                'data' => [
                    'data' => [
                        'id'          => 1,
                        'code'        => 'self',
                        'name'        => 'Self',
                        'description' => 'Hub itself',
                        'is_active'   => true,
                        'created_at'  => '2026-05-01T00:00:00Z',
                    ],
                ],
                'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
            ]);

        Services::injectMock('applicationApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['iam.superadmin-access']],
        ])->get('/admin/iam/applications/1');

        $result->assertStatus(200);
        $body = (string) $result->getBody();
        $this->assertStringContainsString('self', $body);
        $this->assertStringContainsString('Self', $body);
    }

    public function testShowDisplaysNotFoundMessageOnApiFailure(): void
    {
        $mock = $this->createMock(ApplicationApiService::class);
        $mock->method('get')->willReturn([
            'ok' => false, 'status' => 404,
            'data' => [], 'raw' => '', 'headers' => [],
            'messages' => ['Application not found.'], 'fieldErrors' => [],
        ]);

        Services::injectMock('applicationApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['iam.superadmin-access']],
        ])->get('/admin/iam/applications/99');

        $result->assertStatus(200);
        $this->assertStringContainsString('not found', strtolower((string) $result->getBody()));
    }

    public function testDataEndpointReturnsJsonWithListPayload(): void
    {
        $mock = $this->createMock(ApplicationApiService::class);
        $mock->expects($this->once())
            ->method('list')
            ->willReturn([
                'ok' => true, 'status' => 200,
                'data' => ['data' => [
                    ['id' => 1, 'code' => 'self', 'name' => 'Self', 'is_active' => true],
                ], 'pagination' => ['total' => 1]],
                'raw' => '', 'headers' => [], 'messages' => [], 'fieldErrors' => [],
            ]);

        Services::injectMock('applicationApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['permissions' => ['iam.superadmin-access']],
        ])->get('/admin/iam/applications/data');

        $result->assertStatus(200);
        $body = (string) $result->getBody();
        $this->assertStringContainsString('"code"', $body);
        $this->assertStringContainsString('self', $body);
        $this->assertStringContainsString('"pagination"', $body);
    }
}
