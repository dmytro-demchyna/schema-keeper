FROM php:5.6.40-cli-alpine

RUN apk add postgresql-dev postgresql-client wget \
  && docker-php-ext-install pdo_pgsql

RUN mkdir /data \
  && cd /data \
  && wget https://github.com/dmytro-demchyna/schema-keeper/releases/latest/download/schemakeeper.phar \
  && chmod +x /data/schemakeeper.phar

ENTRYPOINT ["/data/schemakeeper.phar"]