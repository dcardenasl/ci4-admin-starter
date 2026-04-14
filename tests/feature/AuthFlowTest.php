<?php

namespace Tests\Feature;

use App\Modules\Auth\Services\AuthApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AuthFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testAttemptLoginSuccessRedirectsToDashboard(): void
    {
        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->once())
            ->method('login')
            ->with($this->callback(static function (array $payload): bool {
                return isset($payload['email'])
                    && isset($payload['password'])
                    && $payload['email'] === 'user@example.com'
                    && $payload['password'] === 'password123';
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [
                        'access_token'  => 'access-token-123',
                        'refresh_token' => 'refresh-token-123',
                        'expires_in'    => 3600,
                        'user'          => [
                            'id'         => 1,
                            'email'      => 'user@example.com',
                            'first_name' => 'John',
                            'last_name'  => 'Doe',
                            'role'       => 'user',
                        ],
                    ],
                ],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('authApiService', $authService);

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/login', [
            'email'    => 'user@example.com',
            'password' => 'password123',
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $this->assertStringContainsString('dashboard', $result->getRedirectUrl());
        $result->assertSessionHas('success');
        $result->assertSessionHas('access_token', 'access-token-123');
        $result->assertSessionHas('refresh_token', 'refresh-token-123');
        $result->assertSessionHas('user');
    }

    public function testAttemptLoginFailureShowsError(): void
    {
        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->once())
            ->method('login')
            ->willReturn([
                'ok'          => false,
                'status'      => 401,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['Invalid email or password'],
                'fieldErrors' => [
                    'email'    => ['Invalid email or password'],
                    'password' => ['Invalid email or password'],
                ],
            ]);

        Services::injectMock('authApiService', $authService);

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/login', [
            'email'    => 'wrong@example.com',
            'password' => 'wrongpassword',
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error');
        $result->assertSessionMissing('access_token');
    }

    public function testAttemptLoginRedirectsIfAlreadyAuthenticated(): void
    {
        $result = $this->withSession([
            'access_token'  => 'existing-token',
            'refresh_token' => 'existing-refresh',
            'user'          => ['id' => 1, 'email' => 'user@example.com', 'role' => 'user'],
        ])->get('/login');

        $result->assertRedirect();
        $this->assertStringContainsString('dashboard', $result->getRedirectUrl());
    }

    public function testLoginValidationFailureShowsFieldErrors(): void
    {
        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/login', [
            'email'    => '',
            'password' => '',
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('fieldErrors');
    }

    public function testAttemptRegisterSuccessRedirectsToLogin(): void
    {
        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->once())
            ->method('register')
            ->with($this->callback(static function (array $payload): bool {
                return isset($payload['first_name'])
                    && isset($payload['last_name'])
                    && isset($payload['email'])
                    && isset($payload['password']);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 201,
                'data'        => [
                    'data' => [
                        'id'         => 2,
                        'email'      => 'newuser@example.com',
                        'first_name' => 'Jane',
                        'last_name'  => 'Smith',
                        'role'       => 'user',
                    ],
                ],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['User registered successfully'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('authApiService', $authService);

        $result = $this->post('/register', [
            'first_name'             => 'Jane',
            'last_name'              => 'Smith',
            'email'                  => 'newuser@example.com',
            'password'               => 'SecurePass123!',
            'password_confirmation'  => 'SecurePass123!',
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $this->assertStringContainsString('login', $result->getRedirectUrl());
        $result->assertSessionHas('success');
    }

    public function testAttemptRegisterValidationFailure(): void
    {
        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/register', [
            'first_name'             => '',
            'last_name'              => '',
            'email'                  => 'invalid-email',
            'password'               => 'short',
            'password_confirmation'  => 'nomatch',
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('fieldErrors');
    }

    public function testRegisterFailureShowsApiError(): void
    {
        $authService = $this->createMock(AuthApiService::class);
        $authService->expects($this->once())
            ->method('register')
            ->willReturn([
                'ok'          => false,
                'status'      => 422,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['Email already registered'],
                'fieldErrors' => [
                    'email' => ['Email already registered'],
                ],
            ]);

        Services::injectMock('authApiService', $authService);

        $result = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_hash(),
        ])->post('/register', [
            'first_name'             => 'Jane',
            'last_name'              => 'Smith',
            'email'                  => 'existing@example.com',
            'password'               => 'SecurePass123!',
            'password_confirmation'  => 'SecurePass123!',
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error');
        $result->assertSessionMissing('access_token');
    }
}
