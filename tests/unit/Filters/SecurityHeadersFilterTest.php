<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\SecurityHeadersFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App as AppConfig;

/**
 * @internal
 */
final class SecurityHeadersFilterTest extends CIUnitTestCase
{
    private SecurityHeadersFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new SecurityHeadersFilter();
    }

    private function createRequest(string $path = '/dashboard'): IncomingRequest
    {
        $uri = $this->createMock(URI::class);
        $uri->method('getPath')->willReturn($path);

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getUri')->willReturn($uri);

        return $request;
    }

    private function createResponse(): Response
    {
        return new Response(new AppConfig());
    }

    private function runAfter(string $path = '/dashboard'): ResponseInterface
    {
        $result = $this->filter->after($this->createRequest($path), $this->createResponse());
        $this->assertInstanceOf(ResponseInterface::class, $result);

        return $result;
    }

    public function testAfterAddsXContentTypeOptionsNosniff(): void
    {
        $this->assertSame('nosniff', $this->runAfter()->getHeaderLine('X-Content-Type-Options'));
    }

    public function testAfterAddsXFrameOptionsDeny(): void
    {
        $this->assertSame('DENY', $this->runAfter()->getHeaderLine('X-Frame-Options'));
    }

    public function testAfterAddsReferrerPolicy(): void
    {
        $this->assertSame(
            'strict-origin-when-cross-origin',
            $this->runAfter()->getHeaderLine('Referrer-Policy')
        );
    }

    public function testAfterAddsPermissionsPolicyDisablingCameraAndGeolocation(): void
    {
        $policy = $this->runAfter()->getHeaderLine('Permissions-Policy');

        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('geolocation=()', $policy);
    }

    public function testAfterAddsHstsOnlyInProduction(): void
    {
        $result = $this->runAfter();

        if (ENVIRONMENT === 'production') {
            $hsts = $result->getHeaderLine('Strict-Transport-Security');
            $this->assertStringContainsString('max-age=31536000', $hsts);
            $this->assertStringContainsString('includeSubDomains', $hsts);
        } else {
            $this->assertFalse(
                $result->hasHeader('Strict-Transport-Security'),
                'HSTS must not be set outside production environments.'
            );
        }
    }

    public function testBeforeIsNoopAndReturnsRequest(): void
    {
        $request = $this->createRequest();

        $result = $this->filter->before($request);

        $this->assertSame($request, $result);
    }
}
