<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Logging;

use App\Libraries\Logging\JsonFileHandler;
use App\Libraries\RequestIdHolder;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Audit B10.2 (2026-05-07): pin the JSON-line shape so log aggregators
 * downstream don't break when the format drifts.
 *
 * @internal
 */
final class JsonFileHandlerTest extends CIUnitTestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/json-handler-test-' . bin2hex(random_bytes(4)) . '/';
        mkdir($this->tmpDir, 0o775, true);
        RequestIdHolder::flush();
        // Required: handler self-disables when LOG_FORMAT != json.
        putenv('LOG_FORMAT=json');
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->tmpDir);
        RequestIdHolder::flush();
        putenv('LOG_FORMAT');
        parent::tearDown();
    }

    public function testHandleWritesOneJsonLinePerCall(): void
    {
        $handler = new JsonFileHandler([
            'handles' => ['error'],
            'path'    => $this->tmpDir,
        ]);

        $this->assertTrue($handler->handle('error', 'first message'));
        $this->assertTrue($handler->handle('error', 'second message'));

        $contents = $this->readSingleLogFile();
        $lines = array_values(array_filter(explode("\n", $contents)));

        $this->assertCount(2, $lines);
        $first = json_decode($lines[0], true);
        $second = json_decode($lines[1], true);

        $this->assertSame('first message', $first['message']);
        $this->assertSame('second message', $second['message']);
        $this->assertSame('error', $first['level']);
    }

    public function testIncludesTimestampAndServiceName(): void
    {
        $handler = new JsonFileHandler([
            'handles' => ['info'],
            'path'    => $this->tmpDir,
        ]);

        $handler->handle('info', 'hello');

        $line = json_decode(trim($this->readSingleLogFile()), true);

        $this->assertArrayHasKey('timestamp', $line);
        $this->assertSame('ci4-admin-starter', $line['service']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            (string) $line['timestamp']
        );
    }

    public function testIncludesRequestIdWhenHolderPopulated(): void
    {
        RequestIdHolder::set('correlation-test-12345678');

        $handler = new JsonFileHandler([
            'handles' => ['warning'],
            'path'    => $this->tmpDir,
        ]);

        $handler->handle('warning', 'something happened');

        $line = json_decode(trim($this->readSingleLogFile()), true);

        $this->assertSame('correlation-test-12345678', $line['request_id']);
    }

    public function testOmitsRequestIdWhenHolderEmpty(): void
    {
        RequestIdHolder::flush();

        $handler = new JsonFileHandler([
            'handles' => ['info'],
            'path'    => $this->tmpDir,
        ]);

        $handler->handle('info', 'no correlation');

        $line = json_decode(trim($this->readSingleLogFile()), true);

        // request_id should be filtered out, not emitted as null.
        $this->assertArrayNotHasKey('request_id', $line);
    }

    private function readSingleLogFile(): string
    {
        $files = glob($this->tmpDir . '*.log') ?: [];
        $this->assertCount(1, $files, 'Exactly one log file should be produced.');

        $contents = file_get_contents($files[0]);
        $this->assertNotFalse($contents);

        return $contents;
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . $entry;
            is_dir($path) ? $this->rrmdir($path . '/') : @unlink($path);
        }
        @rmdir($dir);
    }
}
