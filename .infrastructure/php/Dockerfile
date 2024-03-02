#syntax=docker/dockerfile:1.4

FROM php:8.3-cli-alpine

COPY --from=mlocati/php-extension-installer --link /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer/composer:2-bin --link /composer /usr/bin/composer

RUN apk add --no-cache  \
      git \
    ;

RUN set -eux; \
    install-php-extensions \
    	intl \
        pcov \
        pcntl \
    	zip \
    ;
