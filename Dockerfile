# Etapa 1: compilar o frontend com Vite
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


# Etapa 2: Laravel/PHP
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

# Copia o CSS e JS compilados pelo Vite
COPY --from=frontend /app/public/build /var/www/html/public/build

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-dev \
    --optimize-autoloader

RUN chmod +x /usr/local/bin/inventoryflow-entrypoint

ENTRYPOINT ["inventoryflow-entrypoint"]

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]