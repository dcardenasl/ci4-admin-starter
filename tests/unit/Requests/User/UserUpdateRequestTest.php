<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\User;

use App\Modules\Users\Requests\UserUpdateRequest;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Validation\ValidationInterface;
use Config\Services;

/**
 * @internal
 */
final class UserUpdateRequestTest extends CIUnitTestCase
{
    public function testPayloadOmitsEmailWhenOriginalEmailMatches(): void
    {
        $this->actAsSuperadmin();

        $request = $this->createPostRequest([
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'email'         => 'Jane@Example.com',
            'original_email' => 'jane@example.com',
        ]);

        $formRequest = new UserUpdateRequest($request, $this->createValidationMock());
        $payload = $formRequest->payload();

        $this->assertSame('Jane', $payload['first_name']);
        $this->assertSame('Doe', $payload['last_name']);
        $this->assertArrayNotHasKey('email', $payload);
    }

    public function testPayloadIncludesEmailWhenSuperadminAndOriginalEmailDiffers(): void
    {
        $this->actAsSuperadmin();

        $request = $this->createPostRequest([
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'email'         => 'jane.new@example.com',
            'original_email' => 'jane@example.com',
        ]);

        $formRequest = new UserUpdateRequest($request, $this->createValidationMock());
        $payload = $formRequest->payload();

        $this->assertSame('jane.new@example.com', $payload['email']);
    }

    public function testPayloadOmitsEmailForNonSuperadminEvenWhenSubmitted(): void
    {
        // Non-superadmin actor: any email value coming from the form must be
        // dropped server-side, even if the user tampered the readonly input.
        // The API also enforces this — this is defense-in-depth.
        $this->actAsAdmin();

        $request = $this->createPostRequest([
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'email'          => 'tampered@example.com',
            'original_email' => 'jane@example.com',
        ]);

        $formRequest = new UserUpdateRequest($request, $this->createValidationMock());
        $payload = $formRequest->payload();

        $this->assertArrayNotHasKey('email', $payload);
    }

    protected function tearDown(): void
    {
        if (session() !== null) {
            session()->destroy();
        }
        Services::reset();
        parent::tearDown();
    }

    private function actAsSuperadmin(): void
    {
        session()->set('user', [
            'id'          => 1,
            'permissions' => ['users.read', 'users.write', 'iam.superadmin-access'],
        ]);
    }

    private function actAsAdmin(): void
    {
        session()->set('user', [
            'id'          => 2,
            'permissions' => ['users.read', 'users.write'],
        ]);
    }

    private function createPostRequest(array $post): \CodeIgniter\HTTP\IncomingRequest
    {
        $request = service('request');
        $request->setGlobal('post', $post);

        return $request;
    }

    private function createValidationMock(): ValidationInterface
    {
        return $this->createMock(ValidationInterface::class);
    }
}
