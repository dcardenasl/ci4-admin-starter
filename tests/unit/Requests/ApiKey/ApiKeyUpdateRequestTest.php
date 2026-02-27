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
            'name'              => '  Integration Key  ',
            'isActive'          => '1',
            'rateLimitRequests' => '100',
            'rateLimitWindow'   => '60',
            'userRateLimit'     => '10',
            'ipRateLimit'       => '5',
        ]);

        $formRequest = new ApiKeyUpdateRequest($request, $this->createValidationMock());
        $payload = $formRequest->payload();

        $this->assertSame('Integration Key', $payload['name']);
        $this->assertTrue($payload['isActive']);
        $this->assertSame(100, $payload['rateLimitRequests']);
        $this->assertSame(60, $payload['rateLimitWindow']);
        $this->assertSame(10, $payload['userRateLimit']);
        $this->assertSame(5, $payload['ipRateLimit']);
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
