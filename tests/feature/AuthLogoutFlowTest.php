<?php

namespace Tests\Feature;

use App\Services\AuthApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AuthLogoutFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testLogoutCallsRevokeAndDestroysSession(): void
    {
        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->once())
            ->method('logout')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('authApiService', $authService);

        $result = $this->withSession([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'user'         => ['id' => 1, 'email' => 'admin@example.com', 'role' => 'admin'],
        ])->get('/logout');

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('success');
        $result->assertSessionMissing('access_token');
    }

    public function testLogoutStillDestroysSessionWhenRevokeFails(): void
    {
        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->once())
            ->method('logout')
            ->willThrowException(new \RuntimeException('Network error'));

        Services::injectMock('authApiService', $authService);

        $result = $this->withSession([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'user'         => ['id' => 1, 'email' => 'admin@example.com', 'role' => 'admin'],
        ])->get('/logout');

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('success');
        $result->assertSessionMissing('access_token');
    }
}
