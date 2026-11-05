#!/bin/sh
set -e

[ -f .env ] || cp .env.example .env
composer install --no-interaction --prefer-dist
grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force
php artisan migrate --force
php artisan demo:seed
chmod -R a+rwX storage bootstrap/cache
