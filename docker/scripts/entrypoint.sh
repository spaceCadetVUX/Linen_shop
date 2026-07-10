#!/bin/bash
set -e

cd /backend

LOCK_HASH_FILE="vendor/.composer-lock-hash"
CURRENT_HASH=$(md5sum composer.lock | awk '{print $1}')

if [ -f "$LOCK_HASH_FILE" ] && [ "$(cat "$LOCK_HASH_FILE")" = "$CURRENT_HASH" ]; then
  echo "==> composer.lock unchanged, skipping composer install"
else
  echo "==> Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
  echo "$CURRENT_HASH" > "$LOCK_HASH_FILE"
fi

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Linking storage..."
php artisan storage:link --force

echo "==> Publishing Filament assets..."
php artisan filament:assets

if [ "$APP_ENV" = "production" ]; then
  echo "==> Caching config..."
  php artisan config:cache
else
  # Local/dev: never cache config. A cached config freezes DB_CONNECTION=pgsql
  # and silently ignores phpunit.xml's DB_CONNECTION=sqlite override, which is
  # what let `php artisan test` (RefreshDatabase → migrate:fresh) run against
  # the real dev database and wipe it.
  echo "==> Skipping config cache (APP_ENV=$APP_ENV, not production)..."
  php artisan config:clear
fi

echo "==> Starting PHP-FPM..."
exec php-fpm
