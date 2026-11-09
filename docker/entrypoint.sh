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

# Hapus sentinel lama
rm -f /tmp/app_healthy

echo "================================================================"
echo " Supervisi Keperawatan — Container Bootstrap"
echo "================================================================"

echo "[1/4] Caching application configuration..."
php artisan config:cache

echo "[2/4] Caching routes..."
php artisan route:cache

echo "[3/4] Caching views..."
php artisan view:cache

echo "[4/4] Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

# Pastikan permission folder krusial tetap dimiliki www-data
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Sentinel file: menandai bahwa bootstrap selesai (digunakan Docker healthcheck)
touch /tmp/app_healthy

echo "================================================================"
echo " Bootstrap complete! Starting Supervisord (PHP-FPM & Nginx)..."
echo "================================================================"

exec "$@"

