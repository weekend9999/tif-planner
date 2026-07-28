#!/bin/bash
set -e

PORT="${PORT:-10000}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan migrate --force

if [ "${RUN_DB_SEED:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec apache2-foreground
