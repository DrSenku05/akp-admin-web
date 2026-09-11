FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
COPY . .
COPY cafile/ca.pem /etc/ssl/certs/aiven-ca.pem

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

ENV PORT=10000
EXPOSE 10000
CMD ["sh", "-c", "php artisan config:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT}"]
