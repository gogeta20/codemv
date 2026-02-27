#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    composer install --prefer-dist --no-progress --no-interaction

    php bin/console cache:clear --no-warmup || true

    if [ -n "$DATABASE_URL" ]; then
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
    fi
fi

exec docker-php-entrypoint "$@"
