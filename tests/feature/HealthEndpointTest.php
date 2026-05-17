<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Audit B5.2 (2026-05-06): admin's `/health` endpoint must respond without
 * authentication so load balancers and Kubernetes can probe it.
 *
 * @internal
 */
final class HealthEndpointTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHealthEndpointReturns200WithoutSession(): void
    {
        $result = $this->get('/health');

        $body = (string) $result->getBody();
        $statusCode = $result->response()->getStatusCode();
        $contentType = $result->response()->getHeaderLine('Content-Type');

        $this->assertSame(200, $statusCode, 'status: ' . $statusCode . ' body: ' . substr($body, 0, 300));
        $this->assertStringContainsString('application/json', $contentType, 'Content-Type was: ' . $contentType);

        // The TestResponse may wrap the body in a DOM container for DOMParser;
        // strip outer HTML tags before JSON-parsing.
        $jsonBody = trim(strip_tags($body));
        $this->assertJson($jsonBody, 'Body was not JSON after strip_tags. Got: ' . substr($body, 0, 300));

        /** @var array<string, mixed> $payload */
        $payload = json_decode($jsonBody, true);
        $this->assertSame(true, $payload['ok']);
        $this->assertSame('healthy', $payload['status']);
        $this->assertSame('ci4-admin-starter', $payload['service']);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertArrayHasKey('checks', $payload);
        $this->assertSame('ok', $payload['checks']['writable_dir']);
    }

    public function testHealthEndpointDoesNotRedirectGuestsToLogin(): void
    {
        // Without any session — confirms /health is outside the auth-protected route group.
        $result = $this->get('/health');

        $result->assertStatus(200);
        $result->assertNotRedirect();
    }
}
