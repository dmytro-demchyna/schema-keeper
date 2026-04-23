# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.1] - 2026-04-23

### Fixed
- `verify` unified diff no longer silently drops removed or added objects when the same section also contains a changed object

## [4.0.0] - 2026-04-11

### Added
- Pure PDO database access, removing dependency on external `psql` binary
- Stored procedures support (PostgreSQL 11+)
- Unified diff output for verification failures
- CLI options `--url`, `--skip-schema`, `--skip-extension`, `--skip-section`, `--no-default-skip`
- `.schema-keeper` marker file to prevent accidental directory overwrites
- CLI options `--only-schema` and `--only-section` for inclusive filtering (mutually exclusive with their `--skip-` counterparts)
- `KeeperFactory::createWithDefaults()` — convenience method for library users that applies default skip lists

### Changed
- Differentiated exit codes: `1` for schema diff, `2` for connection error, `3` for configuration error
- Minimum PHP version raised from 5.6 to 7.4
- Minimum PostgreSQL version raised from 9.4 to 10
- CLI interface changed from config file (`-c config.php`) to connection options (`--url` or `-h`/`-p`/`-d`/`-U`/`--password`)
- Command renamed: `save` → `dump`

### Removed
- `deploy` command
- External `psql` binary dependency

## [2.2.0] - 2019-06-05

### Changed
- Pretty print instead of minified print for a json output

## [2.1.1] - 2019-05-27

### Added
- PHAR builder

## [2.1.0] - 2019-05-14

### Added
- More accurate CLI output
- CLI parameter --version 
- Failure on unrecognized CLI parameter
- OS checking

### Changed
- Output with exit-code 1 to STDOUT instead of STDERR for SchemaKeeper's native exceptions

## [2.0.1] - 2019-05-10

### Changed
- `schemakeeper deploy` runs in transaction by default

## [2.0.0] - 2019-05-10

### Added
- bin/schemakeeper

### Changed
- `verifyDump` returns void
- `deployDump` returns object instead of array
- `verifyDump` and `deployDump` throws exception on diff

## [1.0.5] - 2019-05-08

### Added
- Protection from removing all functions using deployDump

### Changed
- Fix bug that provoke error in case running deployDump without transaction 

## [1.0.4] - 2019-05-02

### Added
- Throwing exception if `psql` not installed

## [1.0.3] - 2019-04-17

### Changed
- Exception message in some cases

## [1.0.2] - 2019-04-11

### Changed
- README.md
- "suggest" block in composer.json

## [1.0.1] - 2019-03-22
### Added
- CHANGELOG.md

### Changed
- Keywords and description in composer.json

## [1.0.0] - 2019-03-22