# -------------------------------------------------------------
# Stage 1: Build Frontend Assets with Node.js
# -------------------------------------------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

# Copy package descriptors
COPY package*.json ./

# Install npm dependencies
RUN npm install

# Copy application source code for Vite bundling
COPY . .

# Build production assets (Tailwind v4, Alpine.js, etc.)
RUN npm run build

# -------------------------------------------------------------
# Stage 2: PHP 8.4-FPM Application Environment
# -------------------------------------------------------------
FROM php:8.4-fpm-bookworm

# Set working directory
WORKDIR /var/www/html

# Prevent interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies and build libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    gnupg2 \
    ca-certificates \
    apt-transport-https \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Install Microsoft ODBC Driver 18 for SQL Server (required for sqlsrv & pdo_sqlsrv)
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
        msodbcsql18 \
        unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure and install core PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
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

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application source code
COPY . /var/www/html

# Copy compiled frontend assets from Stage 1
COPY --from=frontend /app/public/build /var/www/html/public/build

# Temporary .env for package discovery during build
RUN if [ ! -f .env ]; then cp .env.example .env; fi \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && rm -f .env

# Set appropriate directory permissions for Laravel runtime
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose PHP-FPM default port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]

