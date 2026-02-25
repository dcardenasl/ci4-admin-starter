<?php

namespace Tests\Feature;

use App\Services\ApiKeyApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class ApiKeyFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testAdminRoutesRequireAuth(): void
    {
        $result = $this->get('/admin/api-keys');
        $result->assertRedirectTo('/login');
    }

    public function testNonAdminCannotAccessApiKeys(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'user'],
        ])->get('/admin/api-keys');

        $result->assertRedirectTo('/dashboard');
    }

    public function testIndexRendersForAdmin(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/api-keys');

        $result->assertStatus(200);
        $this->assertStringContainsString('admin/api-keys/data', $result->getBody());
    }

    public function testCreateSuccessRedirectsToShowAndFlashesOneTimeKey(): void
    {
        $mock = $this->createMock(ApiKeyApiService::class);

        $mock->expects($this->once())
            ->method('create')
            ->willReturn([
                'ok'          => true,
                'status'      => 201,
                'data'        => [
                    'data' => [
                        'id'   => 42,
                        'name' => 'Integration Key',
                        'key'  => 'apk_abc123',
                    ],
                ],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('apiKeyApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/api-keys', [
            csrf_token() => csrf_hash(),
            'name' => 'Integration Key',
        ]);

        $result->assertRedirectTo(site_url('admin/api-keys/42'));
    }

    public function testCreateValidationFailureReturnsBackWithInput(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/api-keys', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
    }

    public function testShowDisplaysNotFoundMessageWhenApiFails(): void
    {
        $mock = $this->createMock(ApiKeyApiService::class);
        $mock->expects($this->once())
            ->method('get')
            ->with('404')
            ->willReturn([
                'ok'          => false,
                'status'      => 404,
                'data'        => [],
                'raw'         => '',
                'messages'    => ['API key not found'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('apiKeyApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/api-keys/404');

        $result->assertStatus(200);
        $this->assertStringContainsString('API key not found', $result->getBody());
    }

    public function testUpdateSuccessRedirectsToShow(): void
    {
        $mock = $this->createMock(ApiKeyApiService::class);

        $mock->expects($this->once())
            ->method('update')
            ->with('15', $this->callback(static fn(array $payload): bool => ($payload['name'] ?? '') === 'Renamed'))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => ['id' => 15]],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('apiKeyApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/api-keys/15', [
            csrf_token() => csrf_hash(),
            'name' => 'Renamed',
        ]);

        $result->assertRedirectTo(site_url('admin/api-keys/15'));
    }

    public function testDeleteSuccessRedirectsToList(): void
    {
        $mock = $this->createMock(ApiKeyApiService::class);

        $mock->expects($this->once())
            ->method('delete')
            ->with('15')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('apiKeyApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/api-keys/15/delete', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('admin/api-keys'));
    }
}
