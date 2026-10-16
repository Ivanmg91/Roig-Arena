#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

chmod -R ug+rwX storage bootstrap/cache || true

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"

    if [ "$DB_FILE" != ":memory:" ]; then
        case "$DB_FILE" in
            /*) SQLITE_PATH="$DB_FILE" ;;
            *) SQLITE_PATH="/var/www/html/$DB_FILE" ;;
        esac

        mkdir -p "$(dirname "$SQLITE_PATH")"
        [ -f "$SQLITE_PATH" ] || touch "$SQLITE_PATH"
    fi
fi

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
