FROM php:5.6.40-cli-stretch

RUN mkdir /usr/share/man/man1 /usr/share/man/man7

RUN apt-get update \
  && apt-get install -y unzip libpq-dev libz-dev postgresql-client \
  && docker-php-ext-install pdo pdo_pgsql zip \
  && pecl install xdebug-2.5.5 \
  && docker-php-ext-enable xdebug

RUN echo 'phar.readonly=0' >> /usr/local/etc/php/php.ini