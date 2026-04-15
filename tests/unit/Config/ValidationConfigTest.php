<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ValidationConfigTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        putenv('FILE_MAX_SIZE');
        unset($_ENV['FILE_MAX_SIZE'], $_SERVER['FILE_MAX_SIZE']);
        parent::tearDown();
    }

    public function testMaxFileSizeBytesUsesEnvWhenPresent(): void
    {
        putenv('FILE_MAX_SIZE=5242880');
        $_ENV['FILE_MAX_SIZE'] = '5242880';
        $_SERVER['FILE_MAX_SIZE'] = '5242880';

        $config = new \Config\Validation();

        $this->assertSame(5242880, $config->maxFileSizeBytes);
    }
}
