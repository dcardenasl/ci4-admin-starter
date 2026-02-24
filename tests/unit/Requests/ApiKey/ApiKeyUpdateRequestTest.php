<?php

namespace Tests\Unit\Requests\ApiKey;

use App\Requests\ApiKey\ApiKeyUpdateRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use CodeIgniter\Validation\ValidationInterface;

/**
 * @internal
 */
final class ApiKeyUpdateRequestTest extends CIUnitTestCase
{
    public function testPayloadNormalizesBooleansAndNumericFields(): void
    {
        $request = $this->createPostRequest([
            'name'                => '  Integration Key  ',
            'is_active'           => '1',
            'rate_limit_requests' => '100',
            'rate_limit_window'   => '60',
            'user_rate_limit'     => '10',
            'ip_rate_limit'       => '5',
        ]);

        $formRequest = new ApiKeyUpdateRequest($request, $this->createValidationMock());
        $payload = $formRequest->payload();

        $this->assertSame('Integration Key', $payload['name']);
        $this->assertTrue($payload['is_active']);
        $this->assertSame(100, $payload['rate_limit_requests']);
        $this->assertSame(60, $payload['rate_limit_window']);
        $this->assertSame(10, $payload['user_rate_limit']);
        $this->assertSame(5, $payload['ip_rate_limit']);
    }

    public function testPayloadReturnsEmptyArrayWhenNothingProvided(): void
    {
        $request = $this->createPostRequest([]);

        $formRequest = new ApiKeyUpdateRequest($request, $this->createValidationMock());

        $this->assertSame([], $formRequest->payload());
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
