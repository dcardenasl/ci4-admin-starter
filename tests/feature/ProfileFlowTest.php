<?php

declare(strict_types=1);

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

    public function testAdminCanUpdateOwnProfile(): void
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
                'data'        => ['data' => ['id' => 15, 'first_name' => 'Admin', 'last_name' => 'Updated', 'email' => 'admin@example.com', 'permissions' => ['users.read', 'users.write', 'audit.read', 'metrics.read', 'apikeys.read', 'apikeys.write', 'iam.superadmin-access']]],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('profileApiService', $profileService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 15, 'email' => 'admin@example.com', 'permissions' => ['users.read', 'users.write', 'audit.read', 'metrics.read', 'apikeys.read', 'apikeys.write', 'iam.superadmin-access']],
        ])->post('/profile', [
            csrf_token() => csrf_hash(),
            'first_name'     => 'Admin',
            'last_name'      => 'Updated',
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('success');
    }

    public function testRegularUserCanUpdateOwnProfile(): void
    {
        // Self-edit is now allowed for any authenticated user — Profile no longer
        // gates by users.write. The API enforces the field-level allowlist
        // (first_name, last_name, avatar_url) on PATCH /auth/me.
        $profileService = $this->createMock(ProfileApiService::class);
        $profileService->expects($this->once())
            ->method('update')
            ->with('22', [
                'first_name' => 'User',
                'last_name'  => 'Updated',
            ])
            ->willReturn([
                'ok'          => true,
                'status'      => 200,
                'data'        => ['data' => ['id' => 22, 'first_name' => 'User', 'last_name' => 'Updated']],
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
                'data'        => ['data' => ['id' => 22, 'first_name' => 'User', 'last_name' => 'Updated', 'email' => 'user@example.com', 'permissions' => []]],
                'raw'         => '',
                'headers'     => [],
                'messages'    => [],
                'fieldErrors' => [],
            ]);

        Services::injectMock('profileApiService', $profileService);

        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 22, 'email' => 'user@example.com', 'permissions' => []],
        ])->post('/profile', [
            csrf_token() => csrf_hash(),
            'first_name'     => 'User',
            'last_name'      => 'Updated',
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('success');
    }

    public function testProfilePageShowsEditableFormForAnyAuthenticatedUser(): void
    {
        $result = $this->withSession([
            'access_token' => 'token',
            'user'         => ['id' => 22, 'email' => 'user@example.com', 'first_name' => 'Jane', 'last_name' => 'Doe', 'permissions' => []],
        ])->get('/profile');

        $result->assertStatus(200);
        $body = $result->getBody();
        // Form is rendered (not a read-only summary), with first_name and last_name inputs editable.
        $this->assertStringContainsString('name="first_name"', $body);
        $this->assertStringContainsString('name="last_name"', $body);
        // Email is shown but immutable — the form does not expose an editable email input.
        $this->assertStringNotContainsString('name="email"', $body);
    }

    public function testRequestPasswordResetUsesForgotPasswordFlow(): void
    {
        $profileService = $this->createMock(ProfileApiService::class);
        $profileService->expects($this->once())
            ->method('forgotPassword')
            ->with(
                'user@example.com',
                $this->callback(static fn (string $baseUrl): bool => $baseUrl !== '')
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
            'user'         => ['id' => 22, 'email' => 'user@example.com', 'permissions' => []],
        ])->post('/profile/request-password-reset', [
            csrf_token() => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('profile'));
        $result->assertSessionHas('success');
    }
}
