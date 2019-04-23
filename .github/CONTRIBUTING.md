# Contributing 

## Environment

Development environment are fully virtualized, so it requires installed [Docker Compose](https://docs.docker.com/compose/).

Please, use steps below to setting the project on your machine:

1. Clone project via `git clone`
1. Open project directory in terminal
1. Execute `docker-compose up -d`
1. Execute `./docker/bin/composer_download.sh`
1. Execute `./docker/bin/composer_install.sh`
1. Execute `./docker/bin/run_tests.sh` to ensure that project works as expected

> Directory `docker/images/postgres/docker-entrypoint-initdb.d/` contains scripts that [container](https://hub.docker.com/_/postgres) will automatically run on startup.

## Workflow

1. Fork the project
1. Make your changes
1. Add tests for it
1. Send a pull request

## Coding guidelines

This project comes with an executable `./docker/bin/code_fix.sh` that you can use to  format source code for compliance with this project's coding guidelines.