<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\RateLimitFilter;
use App\Support\SessionKeys;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * @internal
 */
final class RateLimitFilterTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear any rate limit cache keys between tests
        cache()->clean();
    }

    protected function tearDown(): void
    {
        cache()->clean();
        session()->remove(SessionKeys::USER->value);
        Services::reset();
        parent::tearDown();
    }

    public function testAllowsRequestWhenUnderLimit(): void
    {
        $filter  = new RateLimitFilter();
        $request = $this->makeRequest();

        $result = $filter->before($request);

        $this->assertNull($result, 'First request should be allowed');
    }

    public function testBlocksRequestWhenLimitExceeded(): void
    {
        $filter  = new RateLimitFilter();
        $request = $this->makeRequest();

        // Simulate max requests already recorded (key format mirrors RateLimitFilter::resolveKey)
        $key = 'ratelimit_ip_' . md5('127.0.0.1');
        cache()->save($key, 200, 60); // at the limit

        $result = $filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(429, $result->getStatusCode());
    }

    public function testIncrementsHitCounterOnEachRequest(): void
    {
        $filter  = new RateLimitFilter();
        $request = $this->makeRequest();

        $filter->before($request);
        $filter->before($request);
        $filter->before($request);

        $key  = 'ratelimit_ip_' . md5('127.0.0.1');
        $hits = (int) cache($key);

        $this->assertSame(3, $hits);
    }

    public function testUsesUserIdWhenSessionHasAuthenticatedUser(): void
    {
        session()->set(SessionKeys::USER->value, ['id' => 'user-42', 'role' => 'user']);

        $filter  = new RateLimitFilter();
        $request = $this->makeRequest();

        $filter->before($request);

        $this->assertSame(1, (int) cache('ratelimit_user_' . md5('user-42')));
        $this->assertNull(cache('ratelimit_ip_' . md5('127.0.0.1')));
    }

    public function testAfterDoesNothing(): void
    {
        $filter   = new RateLimitFilter();
        $request  = $this->makeRequest();
        $response = service('response');

        $result = $filter->after($request, $response);

        $this->assertNull($result);
    }

    public function testBlockedResponseIsJson(): void
    {
        $filter  = new RateLimitFilter();
        $request = $this->makeRequest();

        // Pre-fill using the same key formula as RateLimitFilter::resolveKey()
        $key = 'ratelimit_ip_' . md5('127.0.0.1');
        cache()->save($key, 200, 60);

        $result = $filter->before($request);

        $this->assertNotNull($result);
        $this->assertStringContainsString('application/json', $result->getHeaderLine('Content-Type'));

        $body = json_decode((string) $result->getBody(), true);
        $this->assertIsArray($body);
        $this->assertFalse($body['ok']);
        $this->assertArrayHasKey('messages', $body);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeRequest(): IncomingRequest
    {
        $mock = $this->getMockBuilder(IncomingRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIPAddress'])
            ->getMock();

        $mock->method('getIPAddress')->willReturn('127.0.0.1');

        return $mock;
    }
}
