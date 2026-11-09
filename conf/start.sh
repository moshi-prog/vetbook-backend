#!/usr/bin/env bash
set -e

echo "==> Preparing Laravel for production..."

# Discover packages at runtime when environment variables are loaded
echo "==> Discovering packages..."
php artisan package:discover --ansi || true

# Create storage symlink if needed
php artisan storage:link || true

# Cache configs and routes
echo "==> Caching configurations and routes..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run database migrations
echo "==> Running database migrations..."
php artisan migrate --force || true

# Start Supervisor (which manages Nginx + PHP-FPM)
echo "==> Starting web server..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
