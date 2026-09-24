FROM ghcr.io/godiah/php-base:8.4-fpm-alpine AS production

WORKDIR /var/www/html

RUN mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions \
        storage/framework/testing storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage/ bootstrap/cache/ \
    && chmod -R 775 storage/ bootstrap/cache/

EXPOSE 9000

FROM nginx:1.27-alpine AS web

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
