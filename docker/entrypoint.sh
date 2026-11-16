#!/bin/sh
set -e

# Jika container dijalankan bukan untuk supervisord (contoh: artisan migrate atau tinker),
# langsung jalankan perintah tanpa bootstrap penuh.
case "$1" in
    *supervisord*)
        ;;
    *)
        exec "$@"
        ;;
esac

echo "================================================================"
echo " Supervisi Keperawatan — Container Bootstrap"
echo "================================================================"

# Pastikan direktori runtime Laravel tersedia (termasuk saat storage di-mount ke volume)
mkdir -p /var/www/html/storage/fonts \
    /var/www/html/storage/logs \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/app/public

echo "[1/5] Caching application configuration..."
php artisan config:cache

echo "[2/5] Caching routes..."
php artisan route:cache

echo "[3/5] Caching views..."
php artisan view:cache

echo "[4/5] Running PostgreSQL migrations..."
php artisan migrate --database=pgsql --path=database/migrations/pgsql --force

echo "[5/5] Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

# Pastikan kepemilikan seluruh runtime storage/cache dipegang www-data setelah bootstrap artisan
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "================================================================"
echo " Bootstrap complete! Starting Supervisord (PHP-FPM & Nginx)..."
echo "================================================================"

exec "$@"
