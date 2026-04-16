<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Security;

/**
 * @internal
 */
final class SecurityConfigTest extends CIUnitTestCase
{
    public function testCsrfTokenDoesNotRegenerateOnEveryPost(): void
    {
        $config = new Security();

        $this->assertFalse($config->regenerate);
    }
}
