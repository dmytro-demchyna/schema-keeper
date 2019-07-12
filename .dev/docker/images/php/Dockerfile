FROM php:7.1-cli-stretch

RUN mkdir /usr/share/man/man1 /usr/share/man/man7

RUN apt-get update \
  && apt-get install -y unzip libpq-dev libz-dev postgresql-client \
  && docker-php-ext-install pdo_pgsql zip \
  && pecl install xdebug-2.7.2 \
  && docker-php-ext-enable xdebug