FROM php:7.4-cli-bullseye

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
  && apt-get install -y unzip libpq-dev libzip-dev postgresql-client \
  && docker-php-ext-install pdo pdo_pgsql zip \
  && pecl install xdebug-3.1.6 \
  && docker-php-ext-enable xdebug

RUN echo 'phar.readonly=0' >> /usr/local/etc/php/php.ini

WORKDIR /data