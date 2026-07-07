<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Security Headers Filter — admin-starter
 *
 * Adds defense-in-depth HTTP headers to every response. Pairs with CI4's
 * native CSP (`Config\ContentSecurityPolicy`) and the session/CSRF stack;
 * does not replace them.
 *
 * Audit B5.1 (2026-05-06): admin shipped without these headers while
 * api-starter already enforced them. Parity restored here.
 */
class SecurityHeadersFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->setHeader(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), magnetometer=(), gyroscope=()'
        );

        // Conservative CSP: hardens against plugin/base-tag/clickjacking vectors
        // without locking down script/style sources (which would require nonces
        // and break inline Tailwind/Alpine in the admin panel).
        if (! $response->hasHeader('Content-Security-Policy')) {
            $response->setHeader(
                'Content-Security-Policy',
                "object-src 'none'; base-uri 'self'; frame-ancestors 'none'"
            );
        }

        if (ENVIRONMENT === 'production') {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
