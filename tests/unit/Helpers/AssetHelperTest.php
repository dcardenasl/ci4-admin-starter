<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Audit B8.1 (2026-05-06): pin behavior of `asset_url()` /
 * `asset_version()` so the cache-busting contract doesn't drift.
 *
 * @internal
 */
final class AssetHelperTest extends CIUnitTestCase
{
    private string $tmpAsset;

    protected function setUp(): void
    {
        parent::setUp();
        helper('asset');

        // Create a real on-disk file under FCPATH so asset_version() can
        // read its mtime in the fallback path. We use a unique name per
        // test run to avoid collisions.
        $this->tmpAsset = 'assets/test-asset-' . uniqid() . '.css';
        $absolute = FCPATH . $this->tmpAsset;
        @mkdir(dirname($absolute), 0o775, true);
        file_put_contents($absolute, '/* test */');
    }

    protected function tearDown(): void
    {
        @unlink(FCPATH . $this->tmpAsset);
        // Clear env override so it doesn't leak across tests.
        putenv('ASSET_VERSION');
        parent::tearDown();
    }

    public function testAssetUrlAppendsMtimeWhenAssetVersionEnvUnset(): void
    {
        putenv('ASSET_VERSION'); // ensure unset

        $url = asset_url($this->tmpAsset);

        $this->assertStringContainsString($this->tmpAsset, $url);
        $this->assertMatchesRegularExpression(
            '/[?&]v=\d+$/',
            $url,
            'mtime fallback should produce a numeric `v` token. Got: ' . $url
        );
    }

    public function testAssetUrlPrefersExplicitAssetVersionEnv(): void
    {
        putenv('ASSET_VERSION=abc123');

        $url = asset_url($this->tmpAsset);

        $this->assertStringContainsString('v=abc123', $url);
        $this->assertStringNotContainsString(
            'v=' . filemtime(FCPATH . $this->tmpAsset),
            $url,
            'When env override is set, mtime should NOT win.'
        );
    }

    public function testAssetUrlReturnsBareUrlWhenAssetMissing(): void
    {
        putenv('ASSET_VERSION');

        $url = asset_url('assets/does-not-exist.css');

        $this->assertStringNotContainsString(
            'v=',
            $url,
            'Missing file + no env => bare URL (no broken `?v=` token).'
        );
    }

    public function testAssetUrlPreservesExistingQueryString(): void
    {
        putenv('ASSET_VERSION=v42');

        $url = asset_url($this->tmpAsset . '?theme=dark');

        // Existing `?theme=dark` should be preserved; version appended with `&`.
        $this->assertStringContainsString('theme=dark', $url);
        $this->assertStringContainsString('v=v42', $url);
        $this->assertStringContainsString('&', $url, 'Second query parameter must use `&` separator.');
    }

    public function testAssetVersionPriorityIsEnvThenMtime(): void
    {
        putenv('ASSET_VERSION');
        $mtimeVersion = asset_version($this->tmpAsset);
        $this->assertNotSame('', $mtimeVersion);
        $this->assertMatchesRegularExpression('/^\d+$/', $mtimeVersion);

        putenv('ASSET_VERSION=release-2026-05-07');
        $envVersion = asset_version($this->tmpAsset);
        $this->assertSame('release-2026-05-07', $envVersion);
    }

    public function testLeadingSlashIsTolerated(): void
    {
        putenv('ASSET_VERSION=foo');

        $withSlash = asset_url('/' . $this->tmpAsset);
        $withoutSlash = asset_url($this->tmpAsset);

        $this->assertSame($withSlash, $withoutSlash);
    }
}
