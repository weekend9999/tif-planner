#!/bin/bash
set -e

php artisan migrate --force

if [ "${RUN_DB_SEED:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec apache2-foreground