#!/usr/bin/env bash
docker-compose run --rm php /data/vendor/bin/phpunit -c /data/tests/phpunit.xml /data/tests/SchemaKeeper