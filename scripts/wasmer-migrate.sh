#!/usr/bin/env bash
set -e

echo "⏳ Waiting for database..."
sleep 5

echo "🔍 Checking database connection..."
php artisan migrate:status || true

echo "🚀 Running migrations..."
php artisan migrate --force

echo "🌱 Running seeders (optional)..."
php artisan db:seed --force || true

echo "✅ Database ready"
