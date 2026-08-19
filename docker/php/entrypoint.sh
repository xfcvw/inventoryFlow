#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

[ -f .env ] || cp .env.example .env

if [ ! -f vendor/autoload.php ]; then
  echo "[InventoryFlow] Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=base64:' .env; then
  echo "[InventoryFlow] Generating APP_KEY..."
  php artisan key:generate --force
fi

echo "[InventoryFlow] Waiting for PostgreSQL..."
until php -r 'try { new PDO("pgsql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; do
  sleep 2
done

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chmod -R ug+rw storage bootstrap/cache 2>/dev/null || true

php artisan migrate --force
php artisan inventoryflow:upgrade-saas --no-interaction
php artisan db:seed --force

exec "$@"
