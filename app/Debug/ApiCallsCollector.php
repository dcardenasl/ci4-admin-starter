<?php

declare(strict_types=1);

namespace App\Debug;

use CodeIgniter\Debug\Toolbar\Collectors\BaseCollector;

/**
 * CI4 Debug Toolbar collector that records every outbound API call made by
 * ApiClient (and its subclasses) during the current request.
 *
 * Only active when ENVIRONMENT === 'development'. Registered in Config\Toolbar.
 */
class ApiCallsCollector extends BaseCollector
{
    /** @var bool */
    protected $hasTimeline = false;

    /** @var bool */
    protected $hasTabContent = true;

    /** @var bool */
    protected $hasVarData = false;

    /** @var string */
    protected $title = 'API';

    /**
     * @var list<array{method: string, url: string, status: int, latency: int, requestId: string, body: string}>
     */
    private static array $calls = [];

    /**
     * Called by ApiClient::request() for every outbound HTTP call.
     *
     * @param array{method: string, url: string, status: int, latency: int, requestId: string, body: string} $call
     */
    public static function collect(array $call): void
    {
        self::$calls[] = $call;
    }

    public function getBadgeValue(): int
    {
        return count(self::$calls);
    }

    public function isEmpty(): bool
    {
        return self::$calls === [];
    }

    public function display(): string
    {
        if ($this->isEmpty()) {
            return '<p style="color:#6b7280;padding:1rem 0.5rem;font-size:13px;">No API calls in this request.</p>';
        }

        $rows = '';

        foreach (self::$calls as $i => $call) {
            $isOk        = $call['status'] >= 200 && $call['status'] < 300;
            $isZero      = $call['status'] === 0;
            $statusColor = $isOk ? '#16a34a' : ($isZero ? '#9ca3af' : '#dc2626');
            $rowBg       = $i % 2 === 0 ? '#ffffff' : '#f9fafb';

            $decoded  = json_decode($call['body'], true);
            $pretty   = is_array($decoded)
                ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $call['body'];
            $escaped  = htmlspecialchars((string) ($pretty ?: '(empty)'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $bodyId   = 'api-debug-body-' . $i;
            $method   = htmlspecialchars($call['method'], ENT_QUOTES, 'UTF-8');
            $url      = htmlspecialchars($call['url'], ENT_QUOTES, 'UTF-8');
            $reqId    = htmlspecialchars($call['requestId'], ENT_QUOTES, 'UTF-8');

            $rows .= <<<HTML
            <tr style="background:{$rowBg}">
                <td style="padding:6px 10px;border-bottom:1px solid #e5e7eb;font-weight:600;white-space:nowrap">{$method}</td>
                <td style="padding:6px 10px;border-bottom:1px solid #e5e7eb;font-family:monospace;font-size:11px;word-break:break-all">{$url}</td>
                <td style="padding:6px 10px;border-bottom:1px solid #e5e7eb;color:{$statusColor};font-weight:700;white-space:nowrap">{$call['status']}</td>
                <td style="padding:6px 10px;border-bottom:1px solid #e5e7eb;white-space:nowrap">{$call['latency']}ms</td>
                <td style="padding:6px 10px;border-bottom:1px solid #e5e7eb;font-family:monospace;font-size:10px;color:#6b7280;word-break:break-all">{$reqId}</td>
                <td style="padding:6px 10px;border-bottom:1px solid #e5e7eb">
                    <details id="{$bodyId}">
                        <summary style="cursor:pointer;color:#2563eb;font-size:12px;user-select:none">View body</summary>
                        <pre style="margin:6px 0 0;padding:10px;background:#111827;color:#86efac;border-radius:4px;overflow:auto;max-height:280px;font-size:11px;white-space:pre-wrap;word-break:break-word">{$escaped}</pre>
                    </details>
                </td>
            </tr>
            HTML;
        }

        return <<<HTML
        <table style="width:100%;border-collapse:collapse;font-size:12px;font-family:system-ui,sans-serif">
            <thead>
                <tr style="background:#f3f4f6;text-align:left">
                    <th style="padding:7px 10px;border-bottom:2px solid #d1d5db;white-space:nowrap">Method</th>
                    <th style="padding:7px 10px;border-bottom:2px solid #d1d5db">URL</th>
                    <th style="padding:7px 10px;border-bottom:2px solid #d1d5db;white-space:nowrap">Status</th>
                    <th style="padding:7px 10px;border-bottom:2px solid #d1d5db;white-space:nowrap">Latency</th>
                    <th style="padding:7px 10px;border-bottom:2px solid #d1d5db;white-space:nowrap">Request-ID</th>
                    <th style="padding:7px 10px;border-bottom:2px solid #d1d5db">Response Body</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>
        HTML;
    }
}
