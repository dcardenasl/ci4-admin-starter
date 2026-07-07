<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Libraries\ApiClient;
use App\Libraries\ApiClientInterface;
use App\Libraries\DomainApiClient;
use App\Libraries\DomainApiClientInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\DomainApiClient as DomainApiClientConfig;
use Config\Services;

/**
 * @internal
 */
final class DomainApiClientTest extends CIUnitTestCase
{
    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupEnv([
            'domainApiClient.baseUrl',
            'DOMAIN_API_BASE_URL',
            'domainApiClient.timeout',
            'DOMAIN_API_TIMEOUT',
            'domainApiClient.connectTimeout',
            'DOMAIN_API_CONNECT_TIMEOUT',
            'domainApiClient.apiPrefix',
            'DOMAIN_API_PREFIX',
            'domainApiClient.appName',
            'DOMAIN_API_APP_NAME',
            'domainApiClient.appKey',
            'DOMAIN_API_APP_KEY',
            'domainApiClient.healthPaths',
            'DOMAIN_API_HEALTH_PATHS',
            'domainApiClient.logRequests',
            'DOMAIN_API_LOG_REQUESTS',
        ]);

        $this->setEnv('domainApiClient.baseUrl', 'http://localhost:8090');
        $this->setEnv('DOMAIN_API_BASE_URL', 'http://localhost:8090');
    }

    protected function tearDown(): void
    {
        $this->restoreEnv();
        Services::reset();
        parent::tearDown();
    }

    public function testClassImplementsBothInterfaces(): void
    {
        $reflection = new \ReflectionClass(DomainApiClient::class);
        $this->assertTrue($reflection->implementsInterface(DomainApiClientInterface::class));
        $this->assertTrue($reflection->implementsInterface(ApiClientInterface::class));
    }

    public function testExtendsApiClient(): void
    {
        $this->assertTrue(is_subclass_of(DomainApiClient::class, ApiClient::class));
    }

    public function testDomainInterfaceExtendsApiInterface(): void
    {
        $reflection = new \ReflectionClass(DomainApiClientInterface::class);
        $this->assertTrue($reflection->isSubclassOf(ApiClientInterface::class));
    }

    public function testConfigDefaultBaseUrlPointsToPort8090(): void
    {
        $config = new DomainApiClientConfig();
        $this->assertSame('http://localhost:8090', $config->baseUrl);
    }

    public function testConfigInheritsApiClientDefaults(): void
    {
        $config = new DomainApiClientConfig();
        $this->assertSame(15, $config->timeout);
        $this->assertSame(5, $config->connectTimeout);
        $this->assertSame('/api/v1', $config->apiPrefix);
    }

    public function testConfigBaseUrlOverridableViaEnv(): void
    {
        $this->setEnv('domainApiClient.baseUrl', 'http://localhost:9999');
        $config = new DomainApiClientConfig();
        $this->assertSame('http://localhost:9999', $config->baseUrl);
    }

    public function testConfigDottedKeyOverridesUppercase(): void
    {
        $this->setEnv('DOMAIN_API_BASE_URL', 'http://uppercase.example');
        $this->setEnv('domainApiClient.baseUrl', 'http://dotted.example');
        $config = new DomainApiClientConfig();
        $this->assertSame('http://dotted.example', $config->baseUrl);
    }

    public function testConfigDoesNotReadApiClientHubEnvVars(): void
    {
        // Hub env vars must NOT leak into the domain config — otherwise both
        // clients would silently point at the same backend.
        $this->setEnv('API_BASE_URL', 'http://hub.example');
        $this->setEnv('apiClient.baseUrl', 'http://hub.example');

        $config = new DomainApiClientConfig();
        $this->assertSame('http://localhost:8090', $config->baseUrl);
    }

    public function testServicesFactoryReturnsDomainApiClientInterface(): void
    {
        $instance = Services::domainApiClient(false);
        $this->assertInstanceOf(DomainApiClientInterface::class, $instance);
        $this->assertInstanceOf(DomainApiClient::class, $instance);
    }

    public function testServicesFactoryAndApiClientAreDistinct(): void
    {
        $hub    = Services::apiClient(false);
        $domain = Services::domainApiClient(false);

        $this->assertNotSame($hub, $domain);
        $this->assertInstanceOf(DomainApiClientInterface::class, $domain);
        // Domain client must satisfy ApiClientInterface so existing services accept it.
        $this->assertInstanceOf(ApiClientInterface::class, $domain);
    }

    /**
     * @param array<int, string> $keys
     */
    private function backupEnv(array $keys): void
    {
        foreach ($keys as $key) {
            $this->envBackup[$key] = [
                'env' => $_ENV[$key] ?? null,
                'server' => $_SERVER[$key] ?? null,
                'putenv' => getenv($key) === false ? null : getenv($key),
            ];
        }
    }

    private function restoreEnv(): void
    {
        foreach ($this->envBackup as $key => $values) {
            if ($values['env'] === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $values['env'];
            }

            if ($values['server'] === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $values['server'];
            }

            if ($values['putenv'] === null) {
                putenv($key);
            } else {
                putenv($key . '=' . $values['putenv']);
            }
        }
    }

    private function setEnv(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}
