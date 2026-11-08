#!/bin/sh
# Production containers cache the configuration from their environment variables before starting.
set -e

php artisan config:cache --no-interaction > /dev/null
php artisan route:cache --no-interaction > /dev/null
php artisan event:cache --no-interaction > /dev/null

exec "$@"
