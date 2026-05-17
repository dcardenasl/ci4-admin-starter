<?php

declare(strict_types=1);

/**
 * Asset URL helper — audit B8.1 (2026-05-06)
 *
 * Builds versioned URLs for static assets so deployments invalidate the
 * browser cache automatically. Without this, `app.css` and `app.js` ship
 * with stable filenames and a long cache TTL, so users see stale UI for
 * up to a year after a deploy.
 *
 * Version source priority:
 *   1. `ASSET_VERSION` env var — set at deploy time (CI gives it a git
 *      short SHA via `npm run build:all` post-step, or the runtime sets
 *      it explicitly). Most production-correct option.
 *   2. File mtime as a fallback — auto-bumps in dev when Tailwind /
 *      `npm run build:vendor` rewrites the file. Fine for dev, not great
 *      for CDN edge caches because mtime resets when files are
 *      rsync'd / docker-copied.
 *   3. Empty string — degrade to the bare URL. Only happens when the
 *      file doesn't exist (e.g. forgot `npm run build:css`).
 *
 * Usage:
 *   <link rel="stylesheet" href="<?= asset_url('assets/css/app.css') ?>">
 *   <script src="<?= asset_url('assets/js/app.js') ?>"></script>
 */

if (! function_exists('asset_url')) {
    /**
     * Build a cache-busted URL for a static asset.
     *
     * @param string $relative Path relative to `public/`, with or without
     *                         a leading slash (e.g. `assets/css/app.css`).
     */
    function asset_url(string $relative): string
    {
        $relative = ltrim($relative, '/');
        $version = asset_version($relative);

        $url = base_url($relative);

        if ($version === '') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'v=' . rawurlencode($version);
    }
}

if (! function_exists('asset_version')) {
    /**
     * Resolve the version token for an asset path. Public so views that
     * need just the token (e.g. for inline manifest emission) can call it.
     */
    function asset_version(string $relative): string
    {
        // 1. Explicit env override — what production should use.
        $envVersion = getenv('ASSET_VERSION');
        if ($envVersion !== false && $envVersion !== '') {
            return (string) $envVersion;
        }

        $envVersion = env('ASSET_VERSION');
        if (is_string($envVersion) && $envVersion !== '') {
            return $envVersion;
        }

        // 2. File mtime fallback — useful in dev.
        $absolute = FCPATH . ltrim($relative, '/');
        if (is_file($absolute)) {
            $mtime = @filemtime($absolute);
            if ($mtime !== false) {
                return (string) $mtime;
            }
        }

        return '';
    }
}
