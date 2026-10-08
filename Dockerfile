FROM serversideup/php:8.3-fpm-nginx

ENV PHP_OPCACHE_ENABLE=1

COPY --chown=www-data:www-data . /var/www/html

USER www-data
RUN composer install --no-dev --optimize-autoloader --no-interaction