#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

php artisan config:clear || true

echo "[InventoryFlow] Waiting for PostgreSQL..."

until php -r 'try {
    new PDO(
        "pgsql:host=".getenv("DB_HOST").
        ";port=".getenv("DB_PORT").
        ";dbname=".getenv("DB_DATABASE"),
        getenv("DB_USERNAME"),
        getenv("DB_PASSWORD")
    );
    exit(0);
} catch (Throwable $e) {
    exit(1);
}'; do
    echo "[InventoryFlow] PostgreSQL not ready yet..."
    sleep 2
done

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chmod -R ug+rw storage bootstrap/cache 2>/dev/null || true

php artisan migrate --seed --force

exec "$@"