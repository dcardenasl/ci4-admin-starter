<?php

namespace Tests\Unit\Libraries;

use App\Libraries\ApiClient;
use App\Libraries\ApiClientInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\ApiClient as ApiClientConfig;

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
}
