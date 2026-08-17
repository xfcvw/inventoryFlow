FROM php:8.4-fpm-bookworm
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libpq-dev libsqlite3-dev libzip-dev libicu-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
    && docker-php-ext-install bcmath curl intl mbstring opcache pdo_pgsql pdo_sqlite xml zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/inventoryflow.ini
COPY docker/php/entrypoint.sh /usr/local/bin/inventoryflow-entrypoint
RUN chmod +x /usr/local/bin/inventoryflow-entrypoint
ENTRYPOINT ["inventoryflow-entrypoint"]
CMD ["php-fpm"]
