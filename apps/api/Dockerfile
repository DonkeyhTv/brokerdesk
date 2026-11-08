FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache libpq unzip \
    && apk add --no-cache --virtual .build-deps postgresql-dev \
    && docker-php-ext-install pdo_pgsql pcntl opcache \
    && apk del .build-deps

RUN printf 'upload_max_filesize=12M\npost_max_size=13M\nexpose_php=Off\n' > "$PHP_INI_DIR/conf.d/app.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Development: the code is mounted from the host and "api-setup" installs everything.
FROM base AS dev

RUN apk add --no-cache git

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY docker/setup.sh /usr/local/bin/api-setup
RUN chmod +x /usr/local/bin/api-setup

# Production: the code and its dependencies are baked into the image.
FROM base AS prod

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'opcache.enable_cli=1\nopcache.validate_timestamps=0\nopcache.memory_consumption=128\nopcache.max_accelerated_files=20000\n' \
        > "$PHP_INI_DIR/conf.d/opcache.ini"

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/api-entrypoint
RUN chmod +x /usr/local/bin/api-entrypoint

USER www-data
ENTRYPOINT ["api-entrypoint"]
CMD ["php-fpm"]

# Production web server: nginx with the public files, in front of php-fpm.
FROM nginx:stable-alpine AS web

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=prod /app/public /app/public
