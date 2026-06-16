#!/bin/sh
set -e

php artisan optimize:clear || true
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache

exec apache2-foreground
