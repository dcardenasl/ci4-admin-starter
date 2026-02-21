<?php

namespace Tests\Unit\Services;

use App\Libraries\ApiClientInterface;
use App\Services\AuthApiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthApiServiceTest extends CIUnitTestCase
{
    private function createMockClient(array $returnValue): ApiClientInterface
    {
        $mock = $this->createMock(ApiClientInterface::class);

        $mock->method('publicPost')->willReturn($returnValue);
        $mock->method('publicGet')->willReturn($returnValue);
        $mock->method('get')->willReturn($returnValue);
        $mock->method('post')->willReturn($returnValue);
        $mock->method('put')->willReturn($returnValue);

        return $mock;
    }

    public function testLoginReturnsApiResponse(): void
    {
        $expected = [
            'ok'       => true,
            'status'   => 200,
            'data'     => ['access_token' => 'abc123'],
            'raw'      => '',
            'messages' => [],
        ];

        $service = new AuthApiService($this->createMockClient($expected));
        $result = $service->login(['email' => 'test@example.com', 'password' => 'secret']);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('abc123', $result['data']['access_token']);
    }

    public function testLoginFailureReturnsError(): void
    {
        $expected = [
            'ok'       => false,
            'status'   => 401,
            'data'     => [],
            'raw'      => '',
            'messages' => ['Invalid credentials.'],
        ];

        $service = new AuthApiService($this->createMockClient($expected));
        $result = $service->login(['email' => 'bad@example.com', 'password' => 'wrong']);

        $this->assertFalse($result['ok']);
        $this->assertSame(401, $result['status']);
        $this->assertSame('Invalid credentials.', $result['messages'][0]);
    }

    public function testMeReturnsUserData(): void
    {
        $expected = [
            'ok'       => true,
            'status'   => 200,
            'data'     => ['data' => ['id' => 1, 'email' => 'test@example.com']],
            'raw'      => '',
            'messages' => [],
        ];

        $service = new AuthApiService($this->createMockClient($expected));
        $result = $service->me();

        $this->assertTrue($result['ok']);
        $this->assertSame('test@example.com', $result['data']['data']['email']);
    }

    public function testForgotPasswordIncludesClientBaseUrlWhenProvided(): void
    {
        $expected = [
            'ok'       => true,
            'status'   => 200,
            'data'     => [],
            'raw'      => '',
            'messages' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('publicPost')
            ->with('/auth/forgot-password', [
                'email' => 'test@example.com',
                'client_base_url' => 'https://admin.example.com',
            ])
            ->willReturn($expected);

        $service = new AuthApiService($mock);
        $result = $service->forgotPassword('test@example.com', 'https://admin.example.com');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
    }

    public function testResendVerificationIncludesPayload(): void
    {
        $expected = [
            'ok'       => true,
            'status'   => 200,
            'data'     => [],
            'raw'      => '',
            'messages' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('post')
            ->with('/auth/resend-verification', [
                'client_base_url' => 'https://admin.example.com',
            ])
            ->willReturn($expected);

        $service = new AuthApiService($mock);
        $result = $service->resendVerification([
            'client_base_url' => 'https://admin.example.com',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
    }

    public function testLogoutUsesRevokeEndpoint(): void
    {
        $expected = [
            'ok'       => true,
            'status'   => 200,
            'data'     => [],
            'raw'      => '',
            'messages' => [],
        ];

        $mock = $this->createMock(ApiClientInterface::class);
        $mock->expects($this->once())
            ->method('post')
            ->with('/auth/revoke')
            ->willReturn($expected);

        $service = new AuthApiService($mock);
        $result = $service->logout();

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
    }
}
