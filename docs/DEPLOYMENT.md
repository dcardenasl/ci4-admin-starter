# Deployment & DevOps

This document provides guidelines for moving the **CI4 Admin Starter** from development to production.

## ⚙️ Environment Variables (`.env`)

Configure these values in your `.env` file for production. **Never commit your `.env` file to version control.**

### 🖥️ App Settings
- `CI_ENVIRONMENT = production`: Disables the Debug Toolbar and verbose error reporting.
- `app.baseURL = 'https://admin.yourdomain.com/'`: The final public URL (must be HTTPS).
- `app.forceGlobalSecureRequests = true`: Ensures all requests are redirected to HTTPS.
- `app.CSPEnabled = true`: Activates the Content Security Policy headers.
- `cookie.secure = true`: Ensures session cookies are only sent over HTTPS.

### 🔌 API Client Settings
- `apiClient.baseUrl = 'https://api.yourdomain.com'`: The URL of your backend.
- `apiClient.apiPrefix = '/api/v1'`: The base path for API endpoints.
- `apiClient.appKey = 'apk_...'`: (Optional) Your API key for higher rate limits.
- `apiClient.logRequests = false`: Disable in production unless debugging connection issues.
- `WEBAPP_BASE_URL = 'https://admin.yourdomain.com'`: Used for deep-linking in emails sent by the API.

### 📁 Upload Settings
- `FILE_MAX_SIZE = 10485760`: Maximum file size in bytes (10MB). Ensure this matches or is lower than the backend's limit.

### 🗄️ Session storage for multi-server (audit B10.3)

CI4's default `FileHandler` stores sessions on disk under `writable/session`. That works on a single VM but **breaks under any horizontal scale** — a request that hits a different pod has no access to the file, so users get logged out at random.

For any deployment with more than one app pod, switch to Redis (recommended) or the database session handler:

```dotenv
# Recommended for multi-server / k8s deployments.
SESSION_DRIVER=redis
SESSION_SAVE_PATH='tcp://session-redis.internal:6379'

# Alternative: shared database (simpler infra, slower than Redis).
# SESSION_DRIVER=database
# SESSION_SAVE_PATH='ci_sessions'   # table name
```

`Config\Session` (`app/Config/Session.php`) reads these env vars at construction time and switches the `$driver` accordingly. Unknown values fall back to `FileHandler` with a warning logged — a typo never silently locks everyone out.

> **About the token-refresh race:** when admin gets a 401, `ApiClient` refreshes the access token. `$isRefreshing` is a static flag, which is single-process-safe but multi-process-leaky: two PHP-FPM workers can both refresh the same `refresh_token` simultaneously, and the API will revoke the loser's tokens. Switching to Redis sessions does **not** fix this on its own — the refresh window is small enough that the race rarely fires, but if you see "session expired mid-action" reports under load, the long-term fix is a Redis SETNX lock around the refresh call. Tracked as future work.

### 📦 Frontend build (audit B11.4)

Three npm scripts cover the asset surface:

| Script | What it does | When to run |
|---|---|---|
| `npm run build:css` | Tailwind compile `src/css/app.css` → `public/assets/css/app.css` (minified). | Every deploy / every CSS source change. |
| `npm run build:vendor` | Copies `node_modules/alpinejs/dist/cdn.min.js` and `node_modules/lucide/dist/umd/lucide.min.js` to `public/assets/vendor/`. | Once per deploy (after `npm install`); the layout falls back to the pinned jsdelivr CDN if vendored copies are missing. |
| `npm run build:all` | `build:css` + `build:vendor`. | Recommended single command for CI / Dockerfile. |

The Dockerfile (audit B5.3) bakes `npm run build:all` into the image's `asset-build` stage, so production deployments built from the Dockerfile do not need npm at runtime. For non-Docker deploys (e.g. classic shared hosting), run `npm ci && npm run build:all` once after a fresh checkout, then deploy `public/assets/` alongside `app/`.

### 🔄 Asset cache-busting (audit B8.1)

Static assets (`public/assets/css/app.css`, `public/assets/js/app.js`, vendored Alpine/Lucide) are referenced via the `asset_url()` helper, which appends `?v=<token>` to the URL so browsers and CDN edges invalidate stale copies after a deploy.

- `ASSET_VERSION = <git-short-sha>`: **production-correct.** Set this at deploy time to a value that changes on every release (typically a git short SHA, the build timestamp, or a release tag). Recommended pattern in CI:
  ```bash
  echo "ASSET_VERSION=$(git rev-parse --short HEAD)" >> .env
  ```
- **Fallback:** when `ASSET_VERSION` is unset, the helper uses each asset's `filemtime()`. Fine in dev (auto-bumps when Tailwind / `npm run build:vendor` rewrites the file) but unreliable behind containerized rsync where mtimes reset on every layer copy. Always set `ASSET_VERSION` in production.

Implementation: `app/Helpers/asset_helper.php` (loaded globally via `Config\Autoload::$helpers`).

---

## 🔒 Security Hardening

### 1. File Permissions
Only the following directories require write permissions by the web server (e.g., `www-data`):
- `writable/cache/`
- `writable/logs/`
- `writable/session/`
- `writable/uploads/` (if used locally)

**Pro Tip:** Run `chmod -R 775 writable` and `chown -R :www-data writable`.

### 2. Document Root
Your web server **MUST** point to the `public/` directory as its document root. This ensures that the framework core and configuration files are not publicly accessible.

### 3. PHP Configuration
Ensure these values are set in your `php.ini` or virtual host:
- `display_errors = Off`
- `log_errors = On`
- `session.cookie_httponly = 1`
- `session.use_strict_mode = 1`

---

## 🌐 Web Server Examples

### Nginx
```nginx
server {
    listen 80;
    server_name admin.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name admin.yourdomain.com;

    root /var/www/ci4-admin-starter/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
```

---

## 🚀 Production Deployment Checklist

1.  [ ] **Composer:** Run `composer install --no-dev --optimize-autoloader`.
2.  [ ] **Environment:** Set `CI_ENVIRONMENT = production`.
3.  [ ] **HTTPS:** Verify `app.baseURL` uses HTTPS and `app.forceGlobalSecureRequests` is true.
4.  [ ] **API Connection:** Test that the Admin can reach the Backend API (check logs).
5.  [ ] **Assets:** Run `npm ci && npm run build:css` to generate optimized styles.
6.  [ ] **Permissions:** Verify `writable/` is writable by the web server.
7.  [ ] **Security:** Verify `app.CSPEnabled = true` and `cookie.secure = true`.
