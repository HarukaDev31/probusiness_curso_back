#!/bin/sh
set -e

# storage/ viene montado desde la carpeta del servidor: asegurar estructura y permisos
mkdir -p storage/app/public storage/logs \
    storage/framework/cache/data storage/framework/sessions storage/framework/views
chown -R www-data:www-data storage bootstrap/cache

php artisan view:clear >/dev/null 2>&1 || true

exec "$@"
