#!/bin/sh
set -e

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ]; then
    if [ "$(id -u)" = '0' ]; then
        if [ "$(stat -c %u:%g /srv/app)" != "$(id -u www-data):$(id -g www-data)" ]; then
            chown www-data:www-data /srv/app -R
        fi
    fi
fi

exec "$@" 