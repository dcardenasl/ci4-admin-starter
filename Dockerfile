# Multi-stage Dockerfile for CodeIgniter 4 admin (PHP-FPM)
# Audit B5.3 (2026-05-06): hardened to non-root, multi-stage, dev-deps stripped,
# frontend assets built. Pairs with .dockerignore to keep image small.

# ---------- Stage 1: Composer dependencies (production only) ----------
FROM composer:2 AS composer-build

WORKDIR /app

# Install dependencies first (better layer caching) then dump optimized autoload.
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev

# ---------- Stage 2: Frontend assets (Tailwind + vendored Alpine/Lucide) ----------
FROM node:20-alpine AS asset-build

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci --no-audit --no-fund

# Tailwind needs:
#   - src/css/app.css        (entry stylesheet)
#   - app/Views/**/*.php     (content scan)
#   - public/assets/js/**.js (content scan + the vendor target dir)
#   - tailwind.config.js     (config)
COPY src ./src
COPY app ./app
COPY public ./public
COPY tailwind.config.js ./

RUN npm run build:all

# ---------- Stage 3: Production runtime (PHP-FPM, non-root) ----------
FROM php:8.2-fpm-alpine

LABEL maintainer="CodeIgniter 4 Admin Starter"
LABEL description="Production-ready CI4 admin (FPM) — pairs with nginx in a sibling container"

# System dependencies + PHP extensions in one layer.
RUN apk add --no-cache \
        curl \
        libpng-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        libxml2-dev \
    && docker-php-ext-install \
        pdo \
        mbstring \
        intl \
        zip \
        opcache
# Note: fileinfo is statically built into PHP 8.x, no enable step needed.

WORKDIR /var/www/html

# Copy application + composer artifacts from build stage.
COPY --from=composer-build /app /var/www/html

# Overlay the built frontend assets.
COPY --from=asset-build /app/public/assets /var/www/html/public/assets

# Ensure writable directory exists and is owned by www-data.
RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/writable

EXPOSE 9000

# Healthcheck: probe PHP-FPM's listener on :9000 (the upstream webserver
# that exposes /health to clients lives in a separate container).
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 9000) ? 0 : 1);' || exit 1

# Drop root before starting FPM.
USER www-data

CMD ["php-fpm"]
