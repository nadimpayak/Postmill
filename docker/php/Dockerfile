FROM composer:1.8 AS composer
FROM php:7.3-fpm-alpine

RUN set -ex && \
    apk --no-cache add autoconf build-base postgresql-libs postgresql-dev icu \
        icu-dev freetype freetype-dev libjpeg-turbo libjpeg-turbo-dev libpng \
        libpng-dev libwebp libwebp-dev libzip libzip-dev bzip2-dev && \
    docker-php-ext-configure gd \
        --with-gd \
        --with-freetype-dir=/usr/include/ \
        --with-png-dir=/usr/include/ \
        --with-jpeg-dir=/usr/include/ \
        --with-webp-dir=/usr/include && \
    NPROC=$(grep -c ^processor /proc/cpuinfo 2>/dev/null || 1) && \
    docker-php-ext-install -j${NPROC} pdo_pgsql intl gd zip bz2 opcache && \
    pecl install apcu && \
    docker-php-ext-enable apcu && \
    apk del --no-cache autoconf build-base postgresql-dev icu-dev freetype-dev \
        libjpeg-turbo-dev libpng-dev libwebp-dev libzip-dev bzip2-dev

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /tmp

WORKDIR /app
