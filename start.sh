#!/bin/sh
set -e

echo "🚀 Starting Family ERP application..."

echo "⏳ Running migrations..."
php artisan migrate --force || true

echo "⏳ Running seeders..."
php artisan db:seed --force || true

echo "🔄 Caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Starting server..."
php -S 0.0.0.0:8080 -t public
