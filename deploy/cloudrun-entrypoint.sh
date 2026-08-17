#!/usr/bin/env bash
set -euo pipefail
cd /var/www/html
envsubst '${PORT}' < /etc/nginx/templates/inventoryflow.conf.template > /etc/nginx/conf.d/inventoryflow.conf
php artisan config:cache
php artisan route:cache
php artisan view:cache
php-fpm -D
exec nginx -g 'daemon off;'
