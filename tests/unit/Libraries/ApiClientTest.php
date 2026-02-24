<?php

namespace Tests\Unit\Libraries;

use App\Libraries\ApiClient;
use App\Libraries\ApiClientInterface;
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
            static fn(\ReflectionMethod $m) => $m->getName(),
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
        $this->assertSame('test-key', $headers['X-API-Key']);
    }

    protected function tearDown(): void
    {
        session()->remove('locale');
        Services::reset();
        parent::tearDown();
    }

    private function invokeMethod(object $object, string $method): array
    {
        $reflection = new \ReflectionClass($object);
        $reflectionMethod = $reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        /** @var array<string, string> $result */
        $result = $reflectionMethod->invoke($object);

        return $result;
    }
}
