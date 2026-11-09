#!/usr/bin/env bash
set -e

# Create required directories for Laravel and Supervisor logs
mkdir -p /var/log/supervisor storage/framework/cache/data storage/framework/sessions storage/framework/views

echo "==> Preparing Laravel for production..."

# Discover packages and create storage symlink
php artisan package:discover --ansi || true
php artisan storage:link || true

# Cache configurations and routes
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
