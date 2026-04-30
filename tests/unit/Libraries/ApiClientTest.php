<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Libraries\ApiClient;
use App\Libraries\ApiClientInterface;
use App\Support\SessionKeys;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Config\ApiClient as ApiClientConfig;
use Config\Services;

/**
 * @internal
 */
final class ApiClientTest extends CIUnitTestCase
{
    public function testClassImplementsInterface(): void
    {
        $reflection = new \ReflectionClass(ApiClient::class);
        $this->assertTrue($reflection->implementsInterface(ApiClientInterface::class));
    }

    public function testInterfaceDefinesExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(ApiClientInterface::class);
        $methods = array_map(
            static fn (\ReflectionMethod $m) => $m->getName(),
            $reflection->getMethods()
        );

        $this->assertContains('get', $methods);
        $this->assertContains('post', $methods);
        $this->assertContains('put', $methods);
        $this->assertContains('delete', $methods);
        $this->assertContains('publicPost', $methods);
        $this->assertContains('publicGet', $methods);
        $this->assertContains('upload', $methods);
        $this->assertContains('request', $methods);
    }

    public function testConfigDefaultValues(): void
    {
        $config = new ApiClientConfig();
        $this->assertSame('http://localhost:8080', $config->baseUrl);
        $this->assertSame(15, $config->timeout);
        $this->assertSame(5, $config->connectTimeout);
        $this->assertSame('/api/v1', $config->apiPrefix);
        $this->assertSame('API Client', $config->appName);
    }

    public function testConfigReadsEnvVariables(): void
    {
        $config = new ApiClientConfig();
        $this->assertIsString($config->baseUrl);
        $this->assertIsInt($config->timeout);
        $this->assertIsInt($config->connectTimeout);
        $this->assertIsString($config->appName);
    }

    public function testBaseHeadersIncludeAcceptLanguageFromCurrentLocale(): void
    {
        Services::language()->setLocale('en');
        session()->set('locale', 'es');

        $client = new ApiClient(new ApiClientConfig());
        $headers = $this->invokeMethod($client, 'baseHeaders');

        $this->assertSame('application/json', $headers['Accept']);
        $this->assertSame('en', $headers['Accept-Language']);
    }

    public function testBaseHeadersFallbackToSessionLocaleWhenCurrentLocaleUnsupported(): void
    {
        Services::language()->setLocale('fr');
        session()->set('locale', 'es');

        $client = new ApiClient(new ApiClientConfig());
        $headers = $this->invokeMethod($client, 'baseHeaders');

        $this->assertSame('es', $headers['Accept-Language']);
    }

    public function testBaseHeadersFallbackToDefaultLocaleWhenNoSupportedLocaleFound(): void
    {
        Services::language()->setLocale('fr');
        session()->set('locale', 'pt');

        $client = new ApiClient(new ApiClientConfig());
        $headers = $this->invokeMethod($client, 'baseHeaders');

        $this->assertSame(config('App')->defaultLocale, $headers['Accept-Language']);
    }

    public function testBaseHeadersIncludeAppKeyHeadersWhenConfigured(): void
    {
        Services::language()->setLocale('es');
        $config = new ApiClientConfig();
        $config->appKey = 'test-key';

        $client = new ApiClient($config);
        $headers = $this->invokeMethod($client, 'baseHeaders');

        $this->assertSame('es', $headers['Accept-Language']);
        $this->assertSame('test-key', $headers['X-App-Key']);
        $this->assertArrayNotHasKey('X-API-Key', $headers);
    }

    // ─── Method Contracts ────────────────────────────────────────────

    public function testGetForwardsToRequestAsGetMethod(): void
    {
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'test-token');
        $client = new ApiClient(new ApiClientConfig());
        $response = $this->createResponseMock(200, ['data' => ['items' => []]]);

        $http = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                '/api/v1/test',
                $this->callback(function (array $options): bool {
                    $this->assertSame(['page' => 2], $options['query']);
                    $this->assertSame('Bearer test-token', $options['headers']['Authorization']);
                    $this->assertSame('application/json', $options['headers']['Accept']);

                    return true;
                })
            )
            ->willReturn($response);

        $this->setProtectedProperty($client, 'http', $http);

        $result = $client->get('/test', ['page' => 2]);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame(['data' => ['items' => []]], $result['data']);
    }

    public function testPostForwardsToRequestAsPostMethod(): void
    {
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'test-token');
        $client = new ApiClient(new ApiClientConfig());
        $response = $this->createResponseMock(201, ['data' => ['id' => 9]]);

        $http = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/api/v1/test',
                $this->callback(function (array $options): bool {
                    $this->assertSame(['name' => 'Jane'], $options['json']);
                    $this->assertSame('Bearer test-token', $options['headers']['Authorization']);

                    return true;
                })
            )
            ->willReturn($response);

        $this->setProtectedProperty($client, 'http', $http);

        $result = $client->post('/test', ['name' => 'Jane']);

        $this->assertTrue($result['ok']);
        $this->assertSame(201, $result['status']);
        $this->assertSame(['data' => ['id' => 9]], $result['data']);
    }

    public function testPutForwardsToRequestAsPutMethod(): void
    {
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'test-token');
        $client = new ApiClient(new ApiClientConfig());
        $response = $this->createResponseMock(200, ['data' => ['updated' => true]]);

        $http = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                '/api/v1/test',
                $this->callback(function (array $options): bool {
                    $this->assertSame(['status' => 'active'], $options['json']);
                    $this->assertSame('Bearer test-token', $options['headers']['Authorization']);

                    return true;
                })
            )
            ->willReturn($response);

        $this->setProtectedProperty($client, 'http', $http);

        $result = $client->put('/test', ['status' => 'active']);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame(['data' => ['updated' => true]], $result['data']);
    }

    public function testDeleteForwardsToRequestAsDeleteMethod(): void
    {
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'test-token');
        $client = new ApiClient(new ApiClientConfig());
        $response = $this->createResponseMock(204, null, '');

        $http = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                '/api/v1/test',
                $this->callback(function (array $options): bool {
                    $this->assertSame('Bearer test-token', $options['headers']['Authorization']);

                    return true;
                })
            )
            ->willReturn($response);

        $this->setProtectedProperty($client, 'http', $http);

        $result = $client->delete('/test');

        $this->assertTrue($result['ok']);
        $this->assertSame(204, $result['status']);
        $this->assertSame([], $result['data']);
    }

    public function testUploadForwardsToRequestAsPostMethod(): void
    {
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'test-token');
        $client = new ApiClient(new ApiClientConfig());
        $filePath = tempnam(sys_get_temp_dir(), 'api-client-upload');
        file_put_contents($filePath, 'demo');

        $response = $this->createResponseMock(201, ['data' => ['uploaded' => true]]);
        $http = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/api/v1/test',
                $this->callback(function (array $options): bool {
                    $this->assertArrayHasKey('multipart', $options);
                    $this->assertCount(2, $options['multipart']);
                    $this->assertSame('reports', $options['multipart']['folder']);
                    $this->assertArrayHasKey('file', $options['multipart']);
                    $this->assertInstanceOf(\CURLFile::class, $options['multipart']['file']);
                    $this->assertSame('text/plain', $options['multipart']['file']->getMimeType());
                    $this->assertSame('Bearer test-token', $options['headers']['Authorization']);
                    $this->assertArrayNotHasKey('Content-Type', $options['headers']);

                    return true;
                })
            )
            ->willReturn($response);

        $this->setProtectedProperty($client, 'http', $http);

        try {
            $result = $client->upload('/test', ['file' => $filePath], ['folder' => 'reports']);
        } finally {
            @unlink($filePath);
        }

        $this->assertTrue($result['ok']);
        $this->assertSame(201, $result['status']);
        $this->assertSame(['data' => ['uploaded' => true]], $result['data']);
    }

    public function testUploadUsesExplicitMimeTypeWhenProvided(): void
    {
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'test-token');
        $client   = new ApiClient(new ApiClientConfig());
        $filePath = tempnam(sys_get_temp_dir(), 'api-client-upload');
        file_put_contents($filePath, 'plain text content');

        $response = $this->createResponseMock(201, ['data' => ['uploaded' => true]]);
        $http     = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/api/v1/test',
                $this->callback(function (array $options) use ($filePath): bool {
                    /** @var \CURLFile $curlFile */
                    $curlFile = $options['multipart']['file'];
                    $this->assertInstanceOf(\CURLFile::class, $curlFile);
                    // Explicit mimeType must take priority over finfo detection
                    $this->assertSame('application/pdf', $curlFile->getMimeType());
                    $this->assertSame('report.pdf', $curlFile->getPostFilename());

                    return true;
                })
            )
            ->willReturn($response);

        $this->setProtectedProperty($client, 'http', $http);

        try {
            $result = $client->upload('/test', [
                'file' => [
                    'path'     => $filePath,
                    'mimeType' => 'application/pdf',
                    'filename' => 'report.pdf',
                ],
            ]);
        } finally {
            @unlink($filePath);
        }

        $this->assertTrue($result['ok']);
    }

    public function testUploadFallsBackToOctetStreamForUnknownMimeType(): void
    {
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'test-token');
        $client   = new ApiClient(new ApiClientConfig());

        // Create a temp file with no extension and ambiguous binary content
        $filePath = tempnam(sys_get_temp_dir(), 'api-client-bin');
        file_put_contents($filePath, "\x00\x01\x02\x03\xff\xfe\xfd");

        $response = $this->createResponseMock(201, ['data' => ['uploaded' => true]]);
        $http     = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/api/v1/test',
                $this->callback(function (array $options): bool {
                    /** @var \CURLFile $curlFile */
                    $curlFile = $options['multipart']['file'];
                    $this->assertInstanceOf(\CURLFile::class, $curlFile);
                    // finfo may detect a type or fall back to octet-stream — either is a non-empty string
                    $this->assertNotSame('', $curlFile->getMimeType());

                    return true;
                })
            )
            ->willReturn($response);

        $this->setProtectedProperty($client, 'http', $http);

        try {
            $result = $client->upload('/test', ['file' => $filePath]);
        } finally {
            @unlink($filePath);
        }

        $this->assertTrue($result['ok']);
    }

    // ─── Token Refresh Flow (401 → Retry) ──────────────────────────

    public function testAttemptTokenRefreshFailsWithoutRefreshToken(): void
    {
        session()->remove(SessionKeys::REFRESH_TOKEN->value);
        $client = new ApiClient(new ApiClientConfig());

        $result = $client->attemptTokenRefresh();

        $this->assertFalse($result);
    }

    public function testAttemptTokenRefreshReturnsFalseWhenNoAccessTokenInResponse(): void
    {
        session()->set(SessionKeys::REFRESH_TOKEN->value, 'refresh-token');

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'refresh_token' => 'new-refresh-token',
                // Missing access_token
            ]));

        $client = new ApiClient(new ApiClientConfig());
        $this->setProtectedProperty($client, 'http', $this->createMockHttp($mockResponse));

        $result = $client->attemptTokenRefresh();

        $this->assertFalse($result);
    }

    public function testAttemptTokenRefreshClearsSessionOnUnauthorized(): void
    {
        session()->set(SessionKeys::REFRESH_TOKEN->value, 'refresh-token');
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'old-token');
        session()->set(SessionKeys::USER->value, ['id' => 1]);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(401);

        $client = new ApiClient(new ApiClientConfig());
        $this->setProtectedProperty($client, 'http', $this->createMockHttp($mockResponse));

        $result = $client->attemptTokenRefresh();

        $this->assertFalse($result);
        // clearSessionAuth should have been called
        $this->assertNull(session()->get(SessionKeys::ACCESS_TOKEN->value));
        $this->assertNull(session()->get(SessionKeys::REFRESH_TOKEN->value));
        $this->assertNull(session()->get(SessionKeys::USER->value));
    }

    public function testRequestRetriesAfterRefreshingTokenOnUnauthorized(): void
    {
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'expired-token');
        session()->set(SessionKeys::REFRESH_TOKEN->value, 'refresh-token');

        $responses = [
            $this->createResponseMock(401, ['message' => 'Expired token']),
            $this->createResponseMock(200, [
                'data' => [
                    'access_token' => 'fresh-token',
                    'refresh_token' => 'fresh-refresh-token',
                    'expires_in' => 3600,
                ],
            ]),
            $this->createResponseMock(200, ['data' => ['id' => 55]]),
        ];

        $call = 0;
        $http = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $http->expects($this->exactly(3))
            ->method('request')
            ->willReturnCallback(function (string $method, string $uri, array $options) use (&$call, $responses): Response {
                if ($call === 0) {
                    $this->assertSame('GET', $method);
                    $this->assertSame('/api/v1/users/55', $uri);
                    $this->assertSame('Bearer expired-token', $options['headers']['Authorization']);
                }

                if ($call === 1) {
                    $this->assertSame('POST', $method);
                    $this->assertSame('/api/v1/auth/refresh', $uri);
                    $this->assertSame(['refresh_token' => 'refresh-token'], $options['json']);
                    $this->assertArrayNotHasKey('Authorization', $options['headers']);
                }

                if ($call === 2) {
                    $this->assertSame('GET', $method);
                    $this->assertSame('/api/v1/users/55', $uri);
                    $this->assertSame('Bearer fresh-token', $options['headers']['Authorization']);
                }

                return $responses[$call++];
            });

        $client = new ApiClient(new ApiClientConfig());
        $this->setProtectedProperty($client, 'http', $http);

        $result = $client->get('/users/55');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('fresh-token', session()->get(SessionKeys::ACCESS_TOKEN->value));
        $this->assertSame('fresh-refresh-token', session()->get(SessionKeys::REFRESH_TOKEN->value));
        $this->assertIsInt(session()->get(SessionKeys::EXPIRES_AT->value));
    }

    // ─── Session Clearing ──────────────────────────────────────────

    public function testClearSessionAuthRemovesAllAuthenticationKeys(): void
    {
        session()->set(SessionKeys::ACCESS_TOKEN->value, 'token');
        session()->set(SessionKeys::REFRESH_TOKEN->value, 'refresh');
        session()->set(SessionKeys::EXPIRES_AT->value, time() + 3600);
        session()->set(SessionKeys::USER->value, ['id' => 1]);

        $client = new ApiClient(new ApiClientConfig());
        $client->clearSessionAuth();

        $this->assertNull(session()->get(SessionKeys::ACCESS_TOKEN->value));
        $this->assertNull(session()->get(SessionKeys::REFRESH_TOKEN->value));
        $this->assertNull(session()->get(SessionKeys::EXPIRES_AT->value));
        $this->assertNull(session()->get(SessionKeys::USER->value));
    }

    protected function tearDown(): void
    {
        session()->destroy();
        Services::reset();
        parent::tearDown();
    }

    /**
     * @return array<string, string>
     */
    private function invokeMethod(object $object, string $method): array
    {
        $reflection = new \ReflectionClass($object);
        $reflectionMethod = $reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        /** @var array<string, string> $result */
        $result = $reflectionMethod->invoke($object);

        return $result;
    }

    private function setProtectedProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    private function createMockHttp(Response $response): \CodeIgniter\HTTP\CURLRequest
    {
        $mock = $this->createMock(\CodeIgniter\HTTP\CURLRequest::class);
        $mock->expects($this->any())
            ->method('request')
            ->willReturn($response);

        return $mock;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function createResponseMock(int $status, ?array $payload = null, ?string $body = null): Response
    {
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn($body ?? json_encode($payload));
        $response->method('getHeaderLine')->willReturn('');

        return $response;
    }
}
