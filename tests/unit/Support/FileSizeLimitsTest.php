<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\FileSizeLimits;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FileSizeLimitsTest extends CIUnitTestCase
{
    public function testParseIniSizeSupportsCommonUnits(): void
    {
        $this->assertSame(1024, FileSizeLimits::parseIniSize('1K'));
        $this->assertSame(2097152, FileSizeLimits::parseIniSize('2M'));
        $this->assertSame(1073741824, FileSizeLimits::parseIniSize('1G'));
        $this->assertSame(0, FileSizeLimits::parseIniSize(''));
        $this->assertSame(0, FileSizeLimits::parseIniSize(false));
    }

    public function testEffectiveMaxBytesUsesMinimumPositiveLimit(): void
    {
        $configured = FileSizeLimits::configuredMaxBytes();
        $upload = FileSizeLimits::phpUploadMaxBytes();
        $post = FileSizeLimits::phpPostMaxBytes();

        $expected = min(array_filter([$configured, $upload, $post], static fn (int $value): bool => $value > 0));

        $this->assertSame($expected, FileSizeLimits::effectiveMaxBytes());
    }
}
