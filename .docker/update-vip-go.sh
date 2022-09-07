#!/usr/bin/env bash

set -eux;

# Configure global composer to use same repositories and auth.json as the project
if [ ! -f "${COMPOSER_HOME}/composer.json" ]; then
  echo "{}" >> "${COMPOSER_HOME}/composer.json"
fi
if [ -f "${COMPOSER_HOME}/config.json" ]; then
  rm -f "${COMPOSER_HOME}/config.json"
fi
if [ ! -f "${COMPOSER_HOME}/auth.json" ]; then
  composer global config repositories.packagist false
  composer global config repositories.private-packagist composer "https://repo.packagist.com/inpsyde/"
  yes | cp auth.json "${COMPOSER_HOME}/auth.json"
fi

# Require and setup Composer studio
composer global require franzl/studio
if [ ! -f "studio.json" ]; then
  studio load "packages/*"
fi

# Install project dependencies
composer install --prefer-dist
# Prepare VIP env
composer vip -v --local --skip-vip-mu-plugins

# We've experienced corrupted installations of WordPress by Composer VIP plugin, here we fix that
# by using WP CLI to download WordPress.
if [ ! -f "./public/wp-includes/cache.php" ]; then
  printf "Looks like WP installation is corrupted. Fixing...\n"
  wp core download --path=./public --skip-content --force --insecure
fi

# Configure wp-config if not configured yet.
if grep -q "database_name_here" "./wp-config.php"; then
  wp config set DB_NAME wordpress --no-add --type=constant --allow-root
  wp config set DB_USER root --no-add --type=constant --allow-root
  wp config set DB_PASSWORD root --no-add --type=constant --allow-root
  wp config set DB_HOST mysql --no-add --type=constant --allow-root
  wp config shuffle-salts --allow-root
fi

# Install WP multisite if not installed
if ! wp core is-installed; then
  wp core multisite-install \
    --title="Vip Composer Bug8" \
    --url="https://vip-composer-bug8.local/" \
    --admin_user="root" \
    --admin_password="root" \
    --admin_email="root@vip-composer-bug8.local" \
    --skip-email \
    --allow-root
fi

# Install VIP Go MU plugins if not installed yet
if [ ! -f "./vip-go-mu-plugins/README.md" ]; then
  composer vip -v --update-vip-mu-plugins
fi

# Setup and configure VIP object cache dropin if necessary
if [ ! -f "./public/wp-content/object-cache.php" ]; then
  cp ./vip-go-mu-plugins/drop-ins/object-cache/object-cache.php ./public/wp-content/object-cache.php
fi
if grep -q "127.0.0.1:11211" "./public/wp-content/object-cache.php"; then
  sed -i 's/127.0.0.1:11211/memcached:11211/' ./public/wp-content/object-cache.php
fi

# compile assets and download translations
composer -v compile-assets
composer -v wp-translation-downloader:download
