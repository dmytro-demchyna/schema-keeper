# Contributing

## Contributor Code of Conduct

Please note that this project is released with a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms.

## Environment

Development environment is fully virtualized, so it requires installed [Docker Compose](https://docs.docker.com/compose/).

Please, use steps below to set up the project on your machine:

1. Clone project via `git clone`
1. Open project directory in terminal
1. Execute `docker compose up -d`
1. Execute `.dev/docker/bin/composer_install.sh`
1. Execute `.dev/docker/bin/tests_run.sh` to ensure that project works as expected

Two PostgreSQL services are configured: `postgres10` (minimum supported) and `postgres17` (maximum supported). Most tests run against `postgres10`.

## Workflow

1. Fork the project
1. Create a branch from the relevant development branch
1. Make your changes
1. Add or update tests for the behavior you changed
1. Run `.dev/docker/bin/tests_run.sh` to verify tests pass
1. Run `.dev/docker/bin/code_check.sh` to verify code style (phpcs, php-cs-fixer, psalm, phpstan)
1. Send a pull request

## Coding guidelines

Run `.dev/docker/bin/code_check.sh` to verify your code follows the project's coding guidelines.
