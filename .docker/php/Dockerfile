FROM php:7.4-fpm-alpine

ARG COMPOSER_HOME
ARG PROJECT_DOMAIN
ARG GITHUB_USER_NAME

RUN set -eux; \
  apk add --no-cache --virtual .composer-rundeps \
    autoconf \
    automake \
    bash \
    coreutils \
    git \
    jq \
    libpng \
    libpng-dev\
    libwebp \
    libwebp-dev \
    libjpeg-turbo \
    libjpeg-turbo-dev \
    make \
    mercurial \
    openssh-client \
    patch \
    python2 \
    subversion \
    tini \
    unzip \
    zip

ARG VIPGO_USER_ID=1000
ARG VIPGO_GROUP_ID=1000

RUN set -xe; \
    \
    # Delete existing user/group if uid/gid occupied.
    existing_group=$(getent group "${VIPGO_GROUP_ID}" | cut -d: -f1); \
    if [[ -n "${existing_group}" ]]; then delgroup "${existing_group}"; fi; \
    existing_user=$(getent passwd "${VIPGO_USER_ID}" | cut -d: -f1); \
    if [[ -n "${existing_user}" ]]; then deluser "${existing_user}"; fi; \
    \
	addgroup -g "${VIPGO_GROUP_ID}" -S vipgo; \
	adduser -u "${VIPGO_USER_ID}" -D -S -s /bin/bash -G vipgo vipgo; \
	adduser vipgo www-data; \
	sed -i '/^vipgo/s/!/*/' /etc/shadow;

ENV MEMCACHED_DEPS zlib-dev
RUN apk add --no-cache --update zlib
RUN set -xe \
    && apk add --no-cache --update --virtual .phpize-deps $PHPIZE_DEPS \
    && apk add --no-cache --update --virtual .memcache-deps $MEMCACHED_DEPS \
    && pecl install memcache-4.0.5.2 \
    && rm -rf /usr/share/php7 \
    && rm -rf /tmp/* \
    && apk del .memcache-deps .phpize-deps

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-2.9.7 \
    && docker-php-ext-enable xdebug

RUN set -ex \
    && apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS imagemagick-dev libtool \
    && export CFLAGS="$PHP_CFLAGS" CPPFLAGS="$PHP_CPPFLAGS" LDFLAGS="$PHP_LDFLAGS" \
    && pecl install imagick-3.4.3 \
    && docker-php-ext-enable imagick \
    && apk add --no-cache --virtual .imagick-runtime-deps imagemagick \
    && apk del .phpize-deps

RUN set -eux; \
  apk add --no-cache --virtual .build-deps \
    libzip-dev \
    zlib-dev \
  ; \
  docker-php-ext-install -j "$(nproc)" \
    zip \
  ; \
  runDeps="$( \
    scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
      | tr ',' '\n' \
      | sort -u \
      | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )"; \
  apk add --no-cache --virtual .composer-phpext-rundeps $runDeps; \
  apk del .build-deps

RUN docker-php-ext-install mysqli
RUN apk add --no-cache bash less mysql-client netcat-openbsd
RUN apk add --update nodejs npm
RUN apk add yarn

ENV GITHUB_USER_NAME $GITHUB_USER_NAME
COPY ./php/setup-git-user.sh /usr/local/bin/setup-git-user
RUN mkdir -p /home/vipgo/.ssh && \
	chmod +x /usr/local/bin/setup-git-user && \
	setup-git-user

COPY ./php/install-composer.sh /usr/local/bin/install-composer
RUN chmod +x /usr/local/bin/install-composer && install-composer

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && \
    mv wp-cli.phar /usr/local/bin/wp

ENV PROJECT_DOMAIN $PROJECT_DOMAIN
ADD ./certs /certs/
RUN chmod +x /certs/create-cert.sh && /certs/create-cert.sh

RUN curl --location --output /usr/local/bin/mhsendmail https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 && \
    chmod +x /usr/local/bin/mhsendmail

COPY ./update-vip-go.sh /usr/local/bin/update-vip-go
RUN chmod +x /usr/local/bin/update-vip-go

ENV PATH="${COMPOSER_HOME}/vendor/bin/:${PATH}"

USER vipgo

ENV ps1_string='➜  ${PWD##*/} ✗ '
RUN echo "export PS1='$ps1_string'" >> ~/.bashrc
