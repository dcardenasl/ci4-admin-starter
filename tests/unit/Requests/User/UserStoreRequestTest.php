<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\User;

use App\Requests\User\UserStoreRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * @internal
 */
final class UserStoreRequestTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::reset();
        parent::tearDown();
    }

    public function testValidateFailsWithMissingNamesAndInvalidRole(): void
    {
        $request = service('request');
        $request->setGlobal('post', [
            'first_name' => '',
            'last_name' => 'A',
            'email' => 'invalid',
            'role' => 'owner',
        ]);

        $formRequest = new UserStoreRequest($request, service('validation'));

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('first_name', $formRequest->errors());
        $this->assertArrayHasKey('last_name', $formRequest->errors());
        $this->assertArrayHasKey('email', $formRequest->errors());
        $this->assertArrayHasKey('role', $formRequest->errors());
    }
}
