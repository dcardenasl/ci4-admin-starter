<?php

namespace Tests\Feature;

use App\Modules\Profile\Services\ProfileApiService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class ProfileFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testAdminCanUpdateOwnProfileUsingUsersEndpoint(): void
    {
        $profileService = $this->createMock(ProfileApiService::class);
        $profileService->expects($this->once())
            ->method('update')
            ->with('15', [
                'first_name' => 'Admin',
                'last_name'  => 'Updated',
            ])
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => ['id' => 15, 'first_name' => 'Admin', 'last_name' => 'Updated']],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        $profileService->expects($this->once())
            ->method('me')
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => ['id' => 15, 'first_name' => 'Admin', 'last_name' => 'Updated', 'email' => 'admin@example.com', 'role' => 'admin']],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('profileApiService', $profileService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 15, 'email' => 'admin@example.com', 'role' => 'admin'],
        ])->post('/profile', [
            csrf_token() => csrf_hash(),
            'first_name'     => 'Admin',
            'last_name'      => 'Updated',
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('success');
    }

    public function testNonAdminCannotUpdateProfile(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 22, 'email' => 'user@example.com', 'role' => 'user'],
        ])->post('/profile', [
            csrf_token() => csrf_hash(),
            'first_name'     => 'User',
            'last_name'      => 'Updated',
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('error');
    }

    public function testProfilePageShowsReadonlyHintForNonAdmin(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 22, 'email' => 'user@example.com', 'first_name' => 'Jane', 'last_name' => 'Doe', 'role' => 'user'],
        ])->get('/profile');

        $result->assertStatus(200);
        $this->assertStringContainsString('solo lectura', mb_strtolower($result->getBody()));
    }

    public function testRequestPasswordResetUsesForgotPasswordFlow(): void
    {
        $profileService = $this->createMock(ProfileApiService::class);
        $profileService->expects($this->once())
            ->method('forgotPassword')
            ->with(
                'user@example.com',
                $this->callback(static fn(string $baseUrl): bool => $baseUrl !== '')
            )
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => [],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('profileApiService', $profileService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 22, 'email' => 'user@example.com', 'role' => 'user'],
        ])->post('/profile/request-password-reset', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('success');
    }
}
