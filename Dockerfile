# -------------------------------------------------------------
# Stage 1: Build Frontend Assets with Node.js
# -------------------------------------------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

# Copy package descriptors untuk memanfaatkan Docker layer caching
COPY package*.json ./

# Install npm dependencies secara konsisten berdasarkan package-lock.json
RUN npm ci

# Copy application source code for Vite bundling
COPY . .

# Build production assets (Tailwind v4, Alpine.js, etc.)
RUN npm run build

# -------------------------------------------------------------
# Stage 2: PHP 8.4-FPM & Nginx Unified Application Environment
# -------------------------------------------------------------
FROM php:8.4-fpm-bookworm

# Set working directory
WORKDIR /var/www/html

# Prevent interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive

# Install Nginx, Supervisord, system dependencies, and build libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    gnupg2 \
    ca-certificates \
    apt-transport-https \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    libonig-dev \
    tzdata \
    && rm -rf /var/lib/apt/lists/*

ENV TZ=Asia/Jakarta

# Install Microsoft ODBC Driver 18 for SQL Server (required for sqlsrv & pdo_sqlsrv)
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
        msodbcsql18 \
        unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure and install core PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        bcmath \
        gd \
        intl \
        zip \
        opcache

# Install SQL Server and Redis extensions via PECL
RUN pecl install sqlsrv pdo_sqlsrv redis \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv redis

# Production PHP Hardening: aktifkan php.ini-production, security.ini, dan pool tuning PHP-FPM
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/security.ini $PHP_INI_DIR/conf.d/security.ini
COPY docker/php/zz-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-fpm-pool.conf

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Nginx server & rate limiting configurations, enable site, dan arahkan log ke stdout/stderr
COPY docker/nginx/security_http.conf /etc/nginx/conf.d/security_http.conf
COPY docker/nginx/security_headers.conf /etc/nginx/snippets/security_headers.conf
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && mkdir -p /var/log/supervisor /var/run/nginx /var/log/nginx /etc/nginx/snippets \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Copy Supervisord configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Salin manifest Composer dulu untuk memanfaatkan layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies tanpa menjalankan scripts artisan dulu (source belum disalin)
RUN composer install --optimize-autoloader --no-dev --no-scripts --no-interaction

# Copy application source code
COPY . /var/www/html

# Copy compiled frontend assets from Stage 1 directly into the public build directory
COPY --from=frontend /app/public/build /var/www/html/public/build

# Re-generate autoloader & jalankan package:discover dengan dummy key lalu hapus binary composer runtime
ARG BUILD_APP_K="base64:ZG9ja2VyYnVpbGRrZXkxMjM0NTY3ODkwYWJjZGVmZ2g="
RUN APP_KEY="${BUILD_APP_K}" composer dump-autoload --optimize \
    && APP_KEY="${BUILD_APP_K}" php artisan package:discover --ansi \
    && rm -f /usr/bin/composer

# Set appropriate directory permissions and runtime directories for Laravel (Nginx & PHP-FPM run as www-data)
RUN mkdir -p /var/www/html/storage/fonts \
        /var/www/html/storage/logs \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/app/public \
    && touch /var/www/html/storage/logs/laravel.log \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy entrypoint script, ensure LF line endings, and set executable permission
COPY docker/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh

# Expose HTTP port 80 (served by Nginx)
EXPOSE 80

# Configure entrypoint and default command
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
