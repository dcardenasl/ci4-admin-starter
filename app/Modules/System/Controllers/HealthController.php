<?php

declare(strict_types=1);

namespace App\Modules\System\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * HealthController
 *
 * Exposes a lightweight, public `GET /health` endpoint suitable for load
 * balancer / Kubernetes liveness probes. Intentionally does NOT extend
 * `BaseWebController`: it skips view rendering, session bootstrap and
 * other web-oriented overhead so probes stay fast and don't depend on the
 * session store.
 *
 * Audit B5.2 (2026-05-06): admin previously had no /health endpoint, so
 * orchestrators had to probe `/login` (HTML) — fragile and not
 * machine-friendly.
 */
class HealthController extends Controller
{
    public function index(): ResponseInterface
    {
        $writableOk = is_writable(WRITEPATH);
        $status = $writableOk ? 'healthy' : 'degraded';
        $httpCode = $writableOk ? 200 : 503;

        $payload = [
            'ok'        => $writableOk,
            'status'    => $status,
            'service'   => 'ci4-admin-starter',
            'version'   => $this->resolveVersion(),
            'timestamp' => gmdate('c'),
            'checks'    => [
                'writable_dir' => $writableOk ? 'ok' : 'fail',
            ],
        ];

        return $this->response
            ->setStatusCode($httpCode)
            ->setJSON($payload);
    }

    /**
     * Read CHANGELOG-tracked version from composer.json if present, else
     * fall back to "unknown". Cheap on every probe; cached at the OS file
     * cache level.
     */
    private function resolveVersion(): string
    {
        $composerJson = ROOTPATH . 'composer.json';
        if (! is_file($composerJson)) {
            return 'unknown';
        }

        $contents = @file_get_contents($composerJson);
        if ($contents === false) {
            return 'unknown';
        }

        $decoded = json_decode($contents, true);
        if (! is_array($decoded)) {
            return 'unknown';
        }

        $version = $decoded['version'] ?? null;

        return is_string($version) && $version !== '' ? $version : 'unknown';
    }
}
