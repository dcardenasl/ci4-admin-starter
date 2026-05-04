<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Users\Services\UserApiService;
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
                return ($payload['first_name'] ?? null) === 'Jane'
                    && ($payload['last_name'] ?? null) === 'Doe'
                    && ($payload['email'] ?? null) === 'jane@example.com'
                    && ! array_key_exists('password', $payload)
                    && ! array_key_exists('role', $payload);
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
            'user'         => ['permissions' => ['users.read', 'users.write', 'audit.read', 'metrics.read', 'apikeys.read', 'apikeys.write', 'iam.superadmin-access']],
        ])->post('/admin/users', [
            csrf_token() => csrf_hash(),
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'email'          => 'jane@example.com',
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
                    return ($payload['first_name'] ?? null) === 'Jane'
                        && ($payload['last_name'] ?? null) === 'Doe'
                        && ! array_key_exists('password', $payload)
                        && ! array_key_exists('role', $payload)
                        && ! array_key_exists('email', $payload); // Email omitted when same as original
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
            'user'         => ['permissions' => ['users.read', 'users.write', 'audit.read', 'metrics.read', 'apikeys.read', 'apikeys.write', 'iam.superadmin-access']],
        ])->post('/admin/users/101', [
            csrf_token() => csrf_hash(),
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'email'          => 'jane@example.com',
            'original_email' => 'jane@example.com',
            'password'       => 'ShouldNotBeProcessed123',
        ]);

        $result->assertRedirect();
    }

    public function testUpdateOmitsEmailWhenItDidNotChange(): void
    {
        $mock = $this->createMock(UserApiService::class);
        $mock->expects($this->once())
            ->method('update')
            ->with(
                '101',
                $this->callback(static function (array $payload): bool {
                    return ($payload['first_name'] ?? null) === 'Jane'
                        && ($payload['last_name'] ?? null) === 'Doe'
                        && ! array_key_exists('password', $payload)
                        && ! array_key_exists('role', $payload)
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
            'user'         => ['permissions' => ['users.read', 'users.write', 'audit.read', 'metrics.read', 'apikeys.read', 'apikeys.write', 'iam.superadmin-access']],
        ])->post('/admin/users/101', [
            csrf_token() => csrf_hash(),
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'email'          => 'jane@example.com',
            'original_email' => 'jane@example.com',
        ]);

        $result->assertRedirectTo(site_url('admin/users/101'));
    }
}
