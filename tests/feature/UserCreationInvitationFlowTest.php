<?php

namespace Tests\Feature;

use App\Services\UserApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class UserCreationInvitationFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testCreateOmitsPasswordAndReliesOnInvitationFlow(): void
    {
        $mock = $this->createMock(UserApiService::class);
        $mock->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (array $payload): bool {
                return ($payload['firstName'] ?? null) === 'Jane'
                    && ($payload['lastName'] ?? null) === 'Doe'
                    && ($payload['email'] ?? null) === 'jane@example.com'
                    && ($payload['role'] ?? null) === 'user'
                    && ! array_key_exists('password', $payload);
            }))
            ->willReturn([
                'ok'          => true,
                'status'      => 201,
                'data'        => ['data' => ['id' => 101]],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/users', [
            csrf_token() => csrf_hash(),
            'firstName'     => 'Jane',
            'lastName'      => 'Doe',
            'email'          => 'jane@example.com',
            'role'           => 'user',
        ]);

        $result->assertRedirect();
        $this->assertStringContainsString('admin/users', $result->getRedirectUrl());
    }

    public function testUpdateOmitsPasswordEvenWhenSentInRequest(): void
    {
        $mock = $this->createMock(UserApiService::class);
        $mock->expects($this->once())
            ->method('update')
            ->with(
                '101',
                $this->callback(static function (array $payload): bool {
                    return ($payload['firstName'] ?? null) === 'Jane'
                        && ($payload['lastName'] ?? null) === 'Doe'
                        && ($payload['email'] ?? null) === 'jane@example.com'
                        && ($payload['role'] ?? null) === 'admin'
                        && ! array_key_exists('password', $payload);
                })
            )
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => ['id' => 101]],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/users/101', [
            csrf_token() => csrf_hash(),
            'firstName'     => 'Jane',
            'lastName'      => 'Doe',
            'email'          => 'jane@example.com',
            'role'           => 'admin',
            'password'       => 'ShouldNotBeProcessed123',
        ]);

        $result->assertRedirectTo(site_url('admin/users/101'));
    }

    public function testUpdateOmitsEmailWhenItDidNotChange(): void
    {
        $mock = $this->createMock(UserApiService::class);
        $mock->expects($this->once())
            ->method('update')
            ->with(
                '101',
                $this->callback(static function (array $payload): bool {
                    return ($payload['firstName'] ?? null) === 'Jane'
                        && ($payload['lastName'] ?? null) === 'Doe'
                        && ($payload['role'] ?? null) === 'admin'
                        && ! array_key_exists('password', $payload)
                        && ! array_key_exists('email', $payload);
                })
            )
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => ['id' => 101]],
                'raw'         => '',
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $mock);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/users/101', [
            csrf_token() => csrf_hash(),
            'firstName'     => 'Jane',
            'lastName'      => 'Doe',
            'email'          => 'jane@example.com',
            'originalEmail' => 'jane@example.com',
            'role'           => 'admin',
        ]);

        $result->assertRedirectTo(site_url('admin/users/101'));
    }
}
