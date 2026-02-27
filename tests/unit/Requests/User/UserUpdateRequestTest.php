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
            'firstName'     => 'Jane',
            'lastName'      => 'Doe',
            'role'          => 'admin',
            'email'         => 'Jane@Example.com',
            'originalEmail' => 'jane@example.com',
        ]);

        $formRequest = new UserUpdateRequest($request, $this->createValidationMock());
        $payload = $formRequest->payload();

        $this->assertSame('Jane', $payload['firstName']);
        $this->assertSame('Doe', $payload['lastName']);
        $this->assertSame('admin', $payload['role']);
        $this->assertArrayNotHasKey('email', $payload);
    }

    public function testPayloadIncludesEmailWhenOriginalEmailDiffers(): void
    {
        $request = $this->createPostRequest([
            'firstName'     => 'Jane',
            'lastName'      => 'Doe',
            'role'          => 'admin',
            'email'         => 'jane.new@example.com',
            'originalEmail' => 'jane@example.com',
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
