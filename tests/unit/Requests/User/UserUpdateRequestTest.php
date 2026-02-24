<?php

namespace Tests\Unit\Requests\User;

use App\Requests\User\UserUpdateRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use CodeIgniter\Validation\ValidationInterface;

/**
 * @internal
 */
final class UserUpdateRequestTest extends CIUnitTestCase
{
    public function testPayloadOmitsEmailWhenOriginalEmailMatches(): void
    {
        $request = $this->createPostRequest([
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'role'           => 'admin',
            'email'          => 'Jane@Example.com',
            'original_email' => 'jane@example.com',
        ]);

        $formRequest = new UserUpdateRequest($request, $this->createValidationMock());
        $payload = $formRequest->payload();

        $this->assertSame('Jane', $payload['first_name']);
        $this->assertSame('Doe', $payload['last_name']);
        $this->assertSame('admin', $payload['role']);
        $this->assertArrayNotHasKey('email', $payload);
    }

    public function testPayloadIncludesEmailWhenOriginalEmailDiffers(): void
    {
        $request = $this->createPostRequest([
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'role'           => 'admin',
            'email'          => 'jane.new@example.com',
            'original_email' => 'jane@example.com',
        ]);

        $formRequest = new UserUpdateRequest($request, $this->createValidationMock());
        $payload = $formRequest->payload();

        $this->assertSame('jane.new@example.com', $payload['email']);
    }

    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
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
