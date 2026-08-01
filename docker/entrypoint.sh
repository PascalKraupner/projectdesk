#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         storage/app/public \
         storage/fonts \
         bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rw storage bootstrap/cache

# nginx workers run as www-data and spool oversized responses here.
mkdir -p /var/cache/nginx/client_temp \
         /var/cache/nginx/fastcgi_temp \
         /var/cache/nginx/proxy_temp \
         /var/cache/nginx/uwsgi_temp \
         /var/cache/nginx/scgi_temp
chown -R www-data:www-data /var/cache/nginx

php artisan storage:link --force >/dev/null 2>&1 || true

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

exec "$@"
