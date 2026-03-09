#!/usr/bin/env bash
docker compose run --rm -e XDEBUG_MODE=coverage php /data/vendor/bin/phpunit -c /data/tests/phpunit.xml --coverage-clover=/data/coverage.xml ${1:+--testsuite "$1"}
