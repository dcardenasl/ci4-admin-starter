<?php

declare(strict_types=1);

namespace App\Libraries\Logging;

use App\Libraries\RequestIdHolder;
use CodeIgniter\Log\Handlers\BaseHandler;

/**
 * JsonFileHandler — admin-starter (audit B10.2, 2026-05-07)
 *
 * Drop-in replacement for CI4's native `FileHandler` that writes one
 * JSON object per line. Designed to be aggregator-friendly (ELK,
 * Splunk, Loki, Datadog) without requiring Monolog as a Composer
 * dependency.
 *
 * Each line carries:
 *   - `timestamp`  : ISO 8601 UTC
 *   - `level`      : log level (e.g. "error", "info")
 *   - `message`    : the formatted log message (CI4 has already
 *                    interpolated `{user_id}`-style placeholders).
 *   - `request_id` : current correlation ID (audit B10.1) — present
 *                    when `RequestIdHolder` is populated.
 *   - `service`    : "ci4-admin-starter" (constant; lets aggregators
 *                    distinguish admin from api logs at a glance).
 *
 * Configured in `Config\Logger::$handlers` alongside or in place of
 * `FileHandler` when `LOG_FORMAT=json`.
 */
class JsonFileHandler extends BaseHandler
{
    private string $path;
    private string $fileExtension;
    private int $filePermissions;

    /**
     * @param array{handles?: list<string>, path?: string, fileExtension?: string, filePermissions?: int} $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        // The `handles` list in config is the on/off switch; if the
        // operator wants to disable JSON logging, they set
        // `LOG_FORMAT=text` (default) and we short-circuit here so
        // even a misconfigured file path costs nothing.
        if (env('LOG_FORMAT', 'text') !== 'json') {
            // Drop all handled levels — BaseHandler's canHandle() will
            // see an empty list and skip this handler entirely.
            $this->handles = [];
        }

        $this->path = ($config['path'] ?? '') !== '' ? (string) $config['path'] : WRITEPATH . 'logs/';
        $this->fileExtension = ltrim($config['fileExtension'] ?? '.log', '.');
        $this->filePermissions = $config['filePermissions'] ?? 0o644;
    }

    /**
     * Handle a single log message.
     *
     * @param string $level
     * @param string $message
     */
    public function handle($level, $message): bool
    {
        $filepath = $this->path . 'log-json-' . date('Y-m-d') . '.' . $this->fileExtension;

        $newfile = ! is_file($filepath);

        $line = $this->buildJsonLine((string) $level, (string) $message);

        $fp = @fopen($filepath, 'ab');
        if ($fp === false) {
            return false;
        }

        flock($fp, LOCK_EX);
        $written = fwrite($fp, $line);
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($newfile) {
            @chmod($filepath, $this->filePermissions);
        }

        return $written !== false;
    }

    private function buildJsonLine(string $level, string $message): string
    {
        $payload = [
            'timestamp'  => gmdate('c'),
            'level'      => strtolower($level),
            'message'    => $message,
            'service'    => 'ci4-admin-starter',
            'request_id' => RequestIdHolder::get(),
        ];

        // Drop null request_id rather than emitting `"request_id":null`
        // — keeps log analyzers from treating it as a meaningful field.
        $payload = array_filter(
            $payload,
            static fn ($value): bool => $value !== null
        );

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            // Fall back to a minimal line so the log call doesn't disappear.
            $encoded = '{"timestamp":"' . gmdate('c') . '","level":"' . $level . '","message":"<unencodable>"}';
        }

        return $encoded . "\n";
    }
}
