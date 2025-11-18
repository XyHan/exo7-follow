FROM php:8.4.14-fpm-alpine

RUN apk --no-cache add --update \
    git \
    linux-headers \
    libpq-dev

RUN wget https://getcomposer.org/download/2.8.4/composer.phar \
    && mv composer.phar /usr/local/bin/composer && chmod u+x /usr/local/bin/composer \
    && git config --global --add safe.directory /usr/app

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql

COPY docker/php/conf/zzz-php.ini /usr/local/etc/php/conf.d/

WORKDIR /usr/app/
