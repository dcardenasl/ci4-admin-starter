<?php

namespace Tests\Unit\Requests\Auth;

use App\Requests\Auth\LoginRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * @internal
 */
final class LoginRequestTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    // ─── Invalid Inputs ───────────────────────────────────────────

    public function testValidateFailsWithInvalidEmailAndShortPassword(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'not-an-email',
            'password' => '123',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('email', $formRequest->errors());
        $this->assertArrayHasKey('password', $formRequest->errors());
    }

    public function testValidateFailsWithMissingEmail(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'password' => 'valid-password-123',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('email', $formRequest->errors());
    }

    public function testValidateFailsWithMissingPassword(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'test@example.com',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('password', $formRequest->errors());
    }

    public function testValidateFailsWithEmptyEmail(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => '',
            'password' => 'valid-password',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('email', $formRequest->errors());
    }

    public function testValidateFailsWithEmptyPassword(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('password', $formRequest->errors());
    }

    public function testValidateFailsWithPasswordTooShort(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'test@example.com',
            'password' => '12345', // 5 chars, min is 6
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('password', $formRequest->errors());
    }

    public function testValidateFailsWithInvalidEmailFormat(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'invalid-email-format',
            'password' => 'valid-password-123',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('email', $formRequest->errors());
    }

    public function testValidateFailsWithEmailWithoutAtSign(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'testexamplecom',
            'password' => 'valid-password',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('email', $formRequest->errors());
    }

    public function testValidateFailsWithEmailMissingLocalPart(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => '@example.com',
            'password' => 'valid-password',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('email', $formRequest->errors());
    }

    // ─── Valid Inputs ────────────────────────────────────────────

    public function testValidateSucceedsWithValidEmailAndPassword(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'user@example.com',
            'password' => 'valid-password-123',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertTrue($formRequest->validate());
        $this->assertEmpty($formRequest->errors());
    }

    public function testValidateSucceedsWithMinimumPasswordLength(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'user@example.com',
            'password' => '123456', // Exactly 6 characters
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertTrue($formRequest->validate());
    }

    public function testValidateFailsWhenPasswordIsOneCharacterBelowMinimumLength(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'user@example.com',
            'password' => '12345',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('password', $formRequest->errors());
    }

    public function testValidateSucceedsWithLongPassword(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'user@example.com',
            'password' => str_repeat('a', 100),
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $this->assertTrue($formRequest->validate());
    }

    public function testValidateSucceedsWithValidEmailVariants(): void
    {
        $validEmails = [
            'simple@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user123@sub.example.com',
        ];

        foreach ($validEmails as $email) {
            $request = service('request');
            $request->setGlobal('post', [
                'email' => $email,
                'password' => 'valid-password',
            ]);

            $formRequest = new LoginRequest($request, service('validation'));
            $this->assertTrue($formRequest->validate(), "Email '{$email}' should be valid");
        }
    }

    // ─── Payload Method ──────────────────────────────────────────

    public function testPayloadReturnsEmailAndPassword(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $payload = $formRequest->payload();

        $this->assertSame([
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ], $payload);
    }

    public function testPayloadPreservesSubmittedWhitespace(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => '  user@example.com  ',
            'password' => '  password123  ',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $payload = $formRequest->payload();

        $this->assertSame('  user@example.com  ', $payload['email']);
        $this->assertSame('  password123  ', $payload['password']);
    }

    public function testPayloadConvertsToString(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $formRequest = new LoginRequest($request, service('validation'));

        $payload = $formRequest->payload();

        $this->assertIsString($payload['email']);
        $this->assertIsString($payload['password']);
    }

}
