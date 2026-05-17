<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\MaintenanceFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App as AppConfig;

/**
 * Audit B10.4 (2026-05-07): pin maintenance-mode 503 contract.
 *
 * @internal
 */
final class MaintenanceFilterTest extends CIUnitTestCase
{
    private MaintenanceFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new MaintenanceFilter();
        putenv('MAINTENANCE_MODE');
    }

    protected function tearDown(): void
    {
        putenv('MAINTENANCE_MODE');
        putenv('MAINTENANCE_MESSAGE');
        putenv('MAINTENANCE_RETRY_AFTER');
        parent::tearDown();
    }

    private function makeRequest(string $path, string $accept = 'text/html'): IncomingRequest
    {
        $request = new IncomingRequest(
            new AppConfig(),
            new URI('http://localhost' . $path),
            null,
            new UserAgent()
        );
        $request->setHeader('Accept', $accept);

        return $request;
    }

    public function testPassesThroughWhenMaintenanceModeOff(): void
    {
        putenv('MAINTENANCE_MODE');

        $result = $this->filter->before($this->makeRequest('/dashboard'));

        $this->assertNull($result);
    }

    public function testReturns503WhenMaintenanceModeOnForBrowserRequest(): void
    {
        putenv('MAINTENANCE_MODE=true');

        $result = $this->filter->before($this->makeRequest('/dashboard', 'text/html'));

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(503, $result->getStatusCode());
        $this->assertNotEmpty($result->getHeaderLine('Retry-After'));
    }

    public function testReturnsJsonForJsonAcceptHeader(): void
    {
        putenv('MAINTENANCE_MODE=true');

        $result = $this->filter->before($this->makeRequest('/api/something', 'application/json'));

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(503, $result->getStatusCode());

        $body = (string) $result->getBody();
        $payload = json_decode($body, true);
        $this->assertIsArray($payload);
        $this->assertSame('maintenance', $payload['status']);
        $this->assertFalse($payload['ok']);
    }

    public function testHealthBypassesMaintenance(): void
    {
        putenv('MAINTENANCE_MODE=true');

        $result = $this->filter->before($this->makeRequest('/health'));

        $this->assertNull($result, '/health must keep responding even in maintenance.');
    }

    public function testPingBypassesMaintenance(): void
    {
        putenv('MAINTENANCE_MODE=true');

        $result = $this->filter->before($this->makeRequest('/ping'));

        $this->assertNull($result);
    }

    public function testCustomMaintenanceMessageIsRendered(): void
    {
        putenv('MAINTENANCE_MODE=true');
        putenv('MAINTENANCE_MESSAGE=Database upgrade in progress, back at 03:00 UTC');

        $result = $this->filter->before($this->makeRequest('/dashboard', 'text/html'));

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertStringContainsString(
            'Database upgrade in progress, back at 03:00 UTC',
            (string) $result->getBody()
        );
    }

    public function testRetryAfterHeaderUsesEnvOverride(): void
    {
        putenv('MAINTENANCE_MODE=true');
        putenv('MAINTENANCE_RETRY_AFTER=3600');

        $result = $this->filter->before($this->makeRequest('/dashboard'));

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame('3600', $result->getHeaderLine('Retry-After'));
    }
}
