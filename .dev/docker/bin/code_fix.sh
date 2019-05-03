#!/usr/bin/env bash

docker-compose run --rm php php /data/vendor/bin/phpcbf \
    -p \
    --standard=PSR2 \
    /data/src \
    /data/tests