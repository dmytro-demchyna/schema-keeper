#!/usr/bin/env bash
docker-compose run --rm php /data/vendor/bin/phpunit -c /data/tests/phpunit.xml --coverage-clover=/data/coverage.xml /data/tests/SchemaKeeper