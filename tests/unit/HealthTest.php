<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Tests\Support\Libraries\ConfigReader;

/**
 * @internal
 */
final class HealthTest extends CIUnitTestCase
{
    public function testIsDefinedAppPath(): void
    {
        $this->assertTrue(defined('APPPATH'));
    }

    public function testBaseUrlHasBeenSet(): void
    {
        $validation = service('validation');

        $env = false;

        // Check the baseURL in .env
        if (is_file(HOMEPATH . '.env')) {
            $env = preg_grep('/^app\.baseURL = ./', file(HOMEPATH . '.env')) !== false;
        }

        if ($env) {
            // BaseURL in .env is a valid URL?
            // phpunit.xml.dist sets app.baseURL in $_SERVER
            // So if you set app.baseURL in .env, it takes precedence
            $config = new App();
            $this->assertTrue(
                $validation->check($config->baseURL, 'valid_url'),
                'baseURL "' . $config->baseURL . '" in .env is not valid URL',
            );
        }

        // Get the baseURL in app/Config/App.php
        // You can't use Config\App, because phpunit.xml.dist sets app.baseURL
        $reader = new ConfigReader();

        // If baseURL is set in app/Config/App.php, it must be a valid URL.
        // Empty value is allowed when environment-specific .env config is used.
        if ($reader->baseURL !== '') {
            $this->assertTrue(
                $validation->check($reader->baseURL, 'valid_url'),
                'baseURL "' . $reader->baseURL . '" in app/Config/App.php is not valid URL',
            );
        } else {
            $this->assertTrue(true);
        }
    }
}
