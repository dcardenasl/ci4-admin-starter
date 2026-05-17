<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * MaintenanceFilter — admin-starter (audit B10.4, 2026-05-07)
 *
 * Returns `503 Service Unavailable` for every non-probe request when
 * `MAINTENANCE_MODE=true` is set in the environment.
 *
 * Bypassed paths (probes / liveness — orchestrators must keep talking
 * to us so they can detect the moment we're back):
 *   - `/health`
 *   - `/ping`
 *   - `/ready`
 *   - `/live`
 *
 * Wired in `globals.before` so it short-circuits before auth, locale,
 * CSRF — anything stateful.
 *
 * **Operator usage:**
 *   - Toggle on:  `export MAINTENANCE_MODE=true && systemctl reload php-fpm`
 *   - Toggle off: `unset MAINTENANCE_MODE && systemctl reload php-fpm`
 *
 * For zero-downtime deploys, flip the env on the **old** pods, drain
 * traffic to a static maintenance page (or a different service tier),
 * roll new pods, then flip the env off.
 */
class MaintenanceFilter implements FilterInterface
{
    /** Probe / liveness paths that must keep responding even in maintenance. */
    private const BYPASS_PATHS = [
        '/health',
        '/ping',
        '/ready',
        '/live',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $this->isMaintenanceModeOn()) {
            return null;
        }

        if (! $request instanceof IncomingRequest) {
            return null;
        }

        $path = '/' . ltrim($request->getUri()->getPath(), '/');
        foreach (self::BYPASS_PATHS as $bypass) {
            if ($path === $bypass || str_starts_with($path, $bypass . '/')) {
                return null;
            }
        }

        $message = (string) (getenv('MAINTENANCE_MESSAGE') ?: env('MAINTENANCE_MESSAGE', 'Service is temporarily unavailable for maintenance.'));
        $retryAfter = (int) (getenv('MAINTENANCE_RETRY_AFTER') ?: env('MAINTENANCE_RETRY_AFTER', 60));

        $response = Services::response()
            ->setStatusCode(503)
            ->setHeader('Retry-After', (string) max(1, $retryAfter));

        if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return $response->setJSON([
                'ok'      => false,
                'status'  => 'maintenance',
                'message' => $message,
            ]);
        }

        return $response->setBody($this->renderHtml($message));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function isMaintenanceModeOn(): bool
    {
        $raw = (string) (getenv('MAINTENANCE_MODE') ?: env('MAINTENANCE_MODE', ''));

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    private function renderHtml(string $message): string
    {
        $safe = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f9fafb; color: #111827; margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1rem; }
        main { max-width: 32rem; text-align: center; }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        p { color: #4b5563; line-height: 1.5; margin: 0; }
    </style>
</head>
<body>
<main>
    <h1>We'll be right back</h1>
    <p>{$safe}</p>
</main>
</body>
</html>
HTML;
    }
}
