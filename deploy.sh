#!/usr/bin/env bash
#
# MyDentist production deploy. Run from the project root on the server.
# Assumes php / composer / npm are on PATH.
set -euo pipefail

echo "→ Pulling latest code..."
git pull --ff-only

echo "→ Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Building frontend assets..."
npm ci
npm run build

echo "→ Running migrations..."
php artisan migrate --force

echo "→ Rebuilding caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "→ Refreshing SEO data (stats + sitemaps)..."
php artisan stats:rebuild-city-service
php artisan sitemap:generate

echo "→ Restarting the queue workers..."
php artisan queue:restart

echo "✓ Deploy complete."
