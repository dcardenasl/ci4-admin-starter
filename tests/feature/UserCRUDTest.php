<?php

namespace Tests\Feature;

use App\Services\UserApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class UserCRUDTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testShowReturnsUserDetail(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('get')
            ->with('123')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [
                    'data' => [
                        'id'         => 123,
                        'email'      => 'user@example.com',
                        'first_name' => 'John',
                        'last_name'  => 'Doe',
                        'role'       => 'user',
                        'status'     => 'approved',
                    ],
                ],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $userService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/users/123');

        $result->assertOK();
        $this->assertStringContainsString('John', $result->getBody());
        $this->assertStringContainsString('Doe', $result->getBody());
        $this->assertStringContainsString('user@example.com', $result->getBody());
    }

    public function testShowRendersErrorWhenUserNotFound(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('get')
            ->with('999')
            ->willReturn([
                'ok'          => false,
                'status'      => 404,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['User not found'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $userService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/users/999');

        $result->assertOK();
        $this->assertStringContainsString('User not found', $result->getBody());
    }

    public function testEditRedirectsWhenUserNotFound(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('get')
            ->with('999')
            ->willReturn([
                'ok'          => false,
                'status'      => 404,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['User not found'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $userService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->get('/admin/users/999/edit');

        $result->assertRedirect();
        $this->assertStringContainsString('admin/users', $result->getRedirectUrl());
        $result->assertSessionHas('error');
    }

    public function testApproveSuccess(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('approve')
            ->with('123')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => ['id' => 123, 'status' => 'approved']],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['User approved'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $userService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/users/123/approve', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $this->assertStringContainsString('admin/users/123', $result->getRedirectUrl());
        $result->assertSessionHas('success');
    }

    public function testApproveFailure(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('approve')
            ->with('123')
            ->willReturn([
                'ok'          => false,
                'status'      => 400,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['User already approved'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $userService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/users/123/approve', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $this->assertStringContainsString('admin/users/123', $result->getRedirectUrl());
        $result->assertSessionHas('error');
    }

    public function testDeleteSuccess(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('delete')
            ->with('123')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['User deleted'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $userService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/users/123/delete', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $this->assertStringContainsString('admin/users', $result->getRedirectUrl());
        $result->assertSessionHas('success');
    }

    public function testDeleteFailure(): void
    {
        $userService = $this->createMock(UserApiService::class);
        $userService->expects($this->once())
            ->method('delete')
            ->with('123')
            ->willReturn([
                'ok'          => false,
                'status'      => 400,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => ['Cannot delete user with active sessions'],
                'fieldErrors' => [],
            ]);

        Services::injectMock('userApiService', $userService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['role' => 'admin'],
        ])->post('/admin/users/123/delete', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirect();
        $this->assertStringContainsString('admin/users', $result->getRedirectUrl());
        $result->assertSessionHas('error');
    }
}
