#!/usr/bin/env bash

set -eux;

export COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_HOME=/tmp

curl --silent --fail --location --retry 3 --output /tmp/composer-setup.php --url https://getcomposer.org/installer
EXPECTED_CHECKSUM=$(wget https://composer.github.io/installer.sig -q -O -)
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid Composer installer checksum'
    rm -f /tmp/composer-setup.php
    exit 1
fi

php /tmp/composer-setup.php --no-ansi --install-dir=/usr/bin --filename=composer;
composer --ansi --version --no-interaction;
rm -f /tmp/composer-setup.php

find /tmp -type d -exec chmod -v 1777 {} +
