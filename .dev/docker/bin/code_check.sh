#!/usr/bin/env bash
docker compose run --rm php bash -c '\
    /data/vendor/bin/phpcs --standard=/data/phpcs.dist.xml \
    && /data/vendor/bin/php-cs-fixer fix --dry-run --diff \
    && /data/vendor/bin/psalm \
    && /data/vendor/bin/phpstan analyse -c /data/phpstan.neon.dist --memory-limit=512M \
'
