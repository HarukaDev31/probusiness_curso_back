# Imagen de cursoback sobre PHP 8.5.12 (php-fpm).
FROM php:8.5.12-fpm-alpine AS base

# Solo la extensión que usa el proyecto (pdo_mysql)
RUN docker-php-ext-install -j"$(nproc)" pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-app.conf

ENV FPM_PORT=9384

WORKDIR /var/www/html

FROM base AS app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# nginx que sirve public/ y pasa PHP al contenedor app
FROM nginx:1.29-alpine AS web
COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
COPY --from=app /var/www/html/public /var/www/html/public
