# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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