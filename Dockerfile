FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev

# Install PHP extensions required by CodeIgniter 4
RUN docker-php-ext-install \
    pdo \
    mbstring \
    intl \
    zip \
    opcache

# Enable fileinfo (needed for MIME detection in ApiClient)
RUN docker-php-ext-enable fileinfo

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy dependency manifests first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-progress

# Copy application source
COPY . .

# Run post-install scripts after full copy
RUN composer run-script post-install-cmd --no-interaction 2>/dev/null || true

# Set proper permissions for CodeIgniter writable directory
RUN chown -R www-data:www-data writable && chmod -R 775 writable

EXPOSE 9000

# Healthcheck: verify the FPM listener is accepting connections on :9000.
# We use a PHP one-liner (PHP is guaranteed to be in the image) instead of
# curl/nc, since this image ships PHP-FPM only — the upstream webserver
# (nginx/apache) lives in a separate container.
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 9000) ? 0 : 1);' || exit 1

CMD ["php-fpm"]
