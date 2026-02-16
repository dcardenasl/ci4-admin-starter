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
}
