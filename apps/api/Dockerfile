FROM php:8.4-fpm-alpine

RUN apk add --no-cache git unzip postgresql-dev \
    && docker-php-ext-install pdo_pgsql pcntl opcache

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/setup.sh /usr/local/bin/api-setup
RUN chmod +x /usr/local/bin/api-setup

WORKDIR /app
