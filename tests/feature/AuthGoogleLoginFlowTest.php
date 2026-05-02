<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Auth\Services\AuthApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AuthGoogleLoginFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private string|false $previousGoogleClientId = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousGoogleClientId = getenv('GOOGLE_CLIENT_ID');
        putenv('GOOGLE_CLIENT_ID=test-google-client-id');
        $_ENV['GOOGLE_CLIENT_ID'] = 'test-google-client-id';
        $_SERVER['GOOGLE_CLIENT_ID'] = 'test-google-client-id';
    }

    protected function tearDown(): void
    {
        if ($this->previousGoogleClientId === false) {
            putenv('GOOGLE_CLIENT_ID');
            unset($_ENV['GOOGLE_CLIENT_ID'], $_SERVER['GOOGLE_CLIENT_ID']);
        } else {
            putenv('GOOGLE_CLIENT_ID=' . $this->previousGoogleClientId);
            $_ENV['GOOGLE_CLIENT_ID'] = $this->previousGoogleClientId;
            $_SERVER['GOOGLE_CLIENT_ID'] = $this->previousGoogleClientId;
        }

        Services::reset();
        parent::tearDown();
    }

    public function testGoogleLoginSuccessPersistsSessionAndRedirectsToDashboard(): void
    {
        $claims = $this->googleClaims();

        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->once())
            ->method('googleLogin')
            ->with($this->callback(static function (array $payload): bool {
                return isset($payload['id_token'])
                    && is_string($payload['id_token'])
                    && str_contains($payload['id_token'], '.');
            }))
            ->willReturn([
                'ok' => true,
                'status' => 200,
                'data' => [
                    'data' => [
                        'access_token' => 'access-token',
                        'refresh_token' => 'refresh-token',
                        'expires_in' => 3600,
                        'user' => ['id' => 1, 'email' => 'google@example.com', 'permissions' => ['iam.admin-access']],
                    ],
                ],
                'raw' => '',
                'headers' => [],
                'messages' => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('authApiService', $authService);

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/login/google', [
            'id_token' => $claims,
        ]);

        $result->assertRedirect();
        $this->assertStringContainsString('dashboard', $result->getRedirectUrl());
        $result->assertSessionHas('success');
        $result->assertSessionHas('access_token');
        $result->assertSessionHas('refresh_token');
        $result->assertSessionHas('user');
    }

    public function testGoogleLoginPendingApprovalRedirectsToLoginWithError(): void
    {
        $claims = $this->googleClaims();

        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->once())
            ->method('googleLogin')
            ->willReturn([
                'ok' => true,
                'status' => 202,
                'data' => [
                    'data' => [
                        'user' => ['status' => 'pending_approval'],
                    ],
                ],
                'raw' => '',
                'headers' => [],
                'messages' => ['Google sign-in received. Your account is pending admin approval.'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('authApiService', $authService);

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/login/google', [
            'id_token' => $claims,
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');
    }

    public function testGoogleLoginConflictRedirectsToLoginWithError(): void
    {
        $claims = $this->googleClaims();

        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->once())
            ->method('googleLogin')
            ->willReturn([
                'ok' => false,
                'status' => 409,
                'data' => [],
                'raw' => '',
                'headers' => [],
                'messages' => ['This email is linked to a different login provider'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('authApiService', $authService);

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/login/google', [
            'id_token' => $claims,
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');
    }

    public function testGoogleLoginWithoutTokenReturnsError(): void
    {
        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->never())
            ->method('googleLogin');
        Services::injectMock('authApiService', $authService);

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/login/google', [
            'id_token' => '',
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');
    }

    public function testGoogleLoginDisabledRedirectsToLoginWithError(): void
    {
        putenv('GOOGLE_CLIENT_ID=');
        $_ENV['GOOGLE_CLIENT_ID'] = '';
        $_SERVER['GOOGLE_CLIENT_ID'] = '';

        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->never())
            ->method('googleLogin');
        Services::injectMock('authApiService', $authService);

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/login/google', [
            'id_token' => $this->googleClaims(),
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');
    }

    public function testGoogleLoginWithInvalidClaimsRedirectsToLoginWithError(): void
    {
        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->never())
            ->method('googleLogin');
        Services::injectMock('authApiService', $authService);

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/login/google', [
            'id_token' => $this->googleClaims(['aud' => 'unexpected-client-id']),
        ]);

        $result->assertRedirectTo(site_url('login'));
        $result->assertSessionHas('error');
    }

    private function googleClaims(array $overrides = []): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'none', 'typ' => 'JWT']) ?: '{}');
        $payload = $this->base64UrlEncode(json_encode(array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => (string) getenv('GOOGLE_CLIENT_ID'),
            'exp' => time() + 3600,
            'sub' => 'google-user-id',
            'email' => 'google@example.com',
        ], $overrides)) ?: '{}');

        return $header . '.' . $payload . '.signature';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
