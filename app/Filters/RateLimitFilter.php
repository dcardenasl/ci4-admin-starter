<?php

declare(strict_types=1);

namespace App\Filters;

use App\Support\SessionKeys;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RateLimitFilter — per-user (or per-IP for guests) request throttle.
 *
 * Applied to all authenticated routes to prevent API abuse.
 * Defaults: 200 requests per 60 seconds per user (overridable via
 * `ADMIN_RATE_LIMIT_REQUESTS`/`ADMIN_RATE_LIMIT_WINDOW`, see Config\RateLimit).
 *
 * Override via route arguments:
 *   $routes->get('...', [...], ['filter' => 'ratelimit:100,30']);
 *   // => 100 requests per 30 seconds
 *
 * Safe reads (GET/HEAD/OPTIONS) never consume the shared budget — a single
 * admin page load fires several of these at once (data table + filters +
 * dashboard widgets), and throttling them alongside writes means normal
 * navigation can trip the limit on its own.
 */
class RateLimitFilter implements FilterInterface
{
    private const EXEMPT_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * @param list<string>|null $arguments [maxRequests, windowSeconds]
     */
    public function before(RequestInterface $request, $arguments = null): ResponseInterface|null
    {
        if (
            $request instanceof IncomingRequest
            && in_array(strtoupper($request->getMethod()), self::EXEMPT_METHODS, true)
        ) {
            return null;
        }

        [$max, $window] = $this->parseArguments($arguments);

        $key  = $this->resolveKey($request);
        $hits = (int) cache($key);

        if ($hits >= $max) {
            log_message('warning', "[RateLimitFilter] Rate limit exceeded for key={$key} hits={$hits} max={$max}");

            return service('response')
                ->setStatusCode(429)
                ->setContentType('application/json')
                ->setBody((string) json_encode([
                    'ok'       => false,
                    'messages' => ['Too many requests. Please slow down and try again.'],
                ]));
        }

        cache()->save($key, $hits + 1, $window);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ResponseInterface|null
    {
        return null;
    }

    /**
     * Build the cache key: prefer authenticated user ID, fall back to IP.
     * Keys are MD5-hashed to avoid reserved characters ({},():/ etc.) in cache backends.
     */
    private function resolveKey(RequestInterface $request): string
    {
        $user = session()->get(SessionKeys::USER->value);
        $id   = is_array($user) && isset($user['id']) ? (string) $user['id'] : null;

        if ($id !== null && $id !== '') {
            return 'ratelimit_user_' . md5($id);
        }

        return 'ratelimit_ip_' . md5($request->getIPAddress());
    }

    /**
     * @param list<string>|null $arguments
     * @return array{int, int}
     */
    private function parseArguments(?array $arguments): array
    {
        $config = config('RateLimit');
        $max    = $config->maxRequests;
        $window = $config->windowSeconds;

        if (isset($arguments[0]) && is_numeric($arguments[0])) {
            $max = max(1, (int) $arguments[0]);
        }

        if (isset($arguments[1]) && is_numeric($arguments[1])) {
            $window = max(1, (int) $arguments[1]);
        }

        return [$max, $window];
    }
}
