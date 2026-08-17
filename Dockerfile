FROM php:8.4-fpm-bookworm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    && docker-php-ext-install \
    bcmath \
    curl \
    intl \
    mbstring \
    opcache \
    pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/inventoryflow.ini
COPY docker/php/entrypoint.sh /usr/local/bin/inventoryflow-entrypoint

COPY . .

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-dev \
    --optimize-autoloader

RUN chmod +x /usr/local/bin/inventoryflow-entrypoint

ENTRYPOINT ["inventoryflow-entrypoint"]

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]