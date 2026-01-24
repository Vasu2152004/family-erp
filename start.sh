#!/usr/bin/env bash
set -e

echo "🚀 Starting Laravel app on Wasmer..."

# Generate key if missing
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

echo "📦 Running migrations..."
php artisan migrate --force || true

echo "📦 Running seeders..."
php artisan db:seed --force || true

echo "🧹 Optimizing Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🌐 Starting PHP server..."
php -S 0.0.0.0:8080 -t public