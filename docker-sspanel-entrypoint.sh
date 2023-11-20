#!/bin/bash
composer install --no-dev

until nc -z -v -w30 mariadb 3306
do
    echo "Waiting for database connection..."
    sleep 1
done

php xcat Tool importAllSettings
php xcat Update

php-fpm --allow-to-run-as-root
