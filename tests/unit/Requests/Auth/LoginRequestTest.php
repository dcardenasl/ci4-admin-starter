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
}
