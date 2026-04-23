<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Cli;

use PDOException;
use SchemaKeeper\Dto\Result;
use SchemaKeeper\Exception\{KeeperException, NotEquals};
use SchemaKeeper\KeeperFactory;

/** @psalm-suppress UnusedClass */
final class EntryPoint
{
    public const VERSION = 'v4.0.1';

    private ArgvParser $parser;

    private KeeperFactory $keeperFactory;

    private DiffBuilder $diffBuilder;

    public function __construct(
        ArgvParser $parser,
        KeeperFactory $keeperFactory,
        DiffBuilder $diffBuilder
    ) {
        $this->parser = $parser;
        $this->keeperFactory = $keeperFactory;
        $this->diffBuilder = $diffBuilder;
    }

    public function run(array $argv): Result
    {
        if (in_array('--version', $argv, true)) {
            return new Result(self::getVersionText(), 0);
        }

        if (in_array('--help', $argv, true) || count($argv) <= 1) {
            return new Result(self::getVersionText() . PHP_EOL . PHP_EOL . self::getUsageText(), 0);
        }

        try {
            $this->checkRequiredExtensions();

            $request = $this->parser->parse($argv);
            $command = $request->getCommand();
            $params = $request->getParams();
            $path = $request->getPath();
            $credentials = $request->getCredentials();

            $keeper = $this->keeperFactory->createFromCredentials($credentials, $params);

            switch ($command) {
                case 'dump':
                    $keeper->saveDump($path);
                    $result = 'Dump saved ' . $path;
                    break;
                case 'verify':
                    $keeper->verifyDump($path);
                    $result = 'Dump verified ' . $path;
                    break;
                default:
                    throw new KeeperException(
                        'Unrecognized command: ' . $command . '. Available commands: dump, verify',
                    );
            }

            return new Result('Success: ' . $result, 0);
        } catch (NotEquals $e) {
            $diff = $this->diffBuilder->format($e->getExpected(), $e->getActual());

            return new Result('Failure: Dump and current database are not equal:' . PHP_EOL . $diff, 1);
        } catch (KeeperException $e) {
            return new Result('Failure: ' . $e->getMessage(), 3);
        } catch (PDOException $e) {
            return new Result('Failure: ' . $e->getMessage(), 2);
        }
    }

    private function checkRequiredExtensions(): void
    {
        if (!extension_loaded('pdo')) {
            throw new KeeperException('Required PHP extension "pdo" is not loaded');
        }

        if (!extension_loaded('pdo_pgsql')) {
            throw new KeeperException('Required PHP extension "pdo_pgsql" is not loaded');
        }
    }

    public static function getVersionText(): string
    {
        return 'SchemaKeeper ' . self::VERSION . ' by Dmytro Demchyna and contributors.';
    }

    public static function getUsageText(): string
    {
        return 'Usage: schemakeeper <command> <dump-dir> [options]' . PHP_EOL . PHP_EOL
            . 'Example: schemakeeper dump /path/to/dump -h localhost -p 5432 -d mydb -U postgres'
            . PHP_EOL . PHP_EOL
            . 'Available commands:' . PHP_EOL
            . '  dump         Dump database structure to dump directory' . PHP_EOL
            . '  verify       Verify database structure against dump' . PHP_EOL . PHP_EOL
            . 'Connection options:' . PHP_EOL
            . '  -h, --host          Database host (required)' . PHP_EOL
            . '  -p, --port          Database port (required)' . PHP_EOL
            . '  -d, --dbname        Database name (required)' . PHP_EOL
            . '  -U, --username      Database user (required)' . PHP_EOL
            . '      --password      Database password (optional)' . PHP_EOL
            . '      --url           Connection URL (alternative to individual options)' . PHP_EOL
            . '                      Format: "postgresql://user:password@host:port/dbname"'
            . PHP_EOL . PHP_EOL
            . 'Filter options:' . PHP_EOL
            . '      --skip-schema       Schema name to skip (repeatable, adds to defaults)' . PHP_EOL
            . '      --skip-extension    Extension name to skip (repeatable, adds to defaults)' . PHP_EOL
            . '      --skip-section      Section type to skip (repeatable)' . PHP_EOL
            . '                          Valid values: tables, views, materialized_views, types,' . PHP_EOL
            . '                          functions, triggers, sequences, procedures' . PHP_EOL
            . '      --only-schema       Include only this schema (repeatable, exclusive with --skip-schema)'
            . PHP_EOL
            . '      --only-section      Include only this section type (repeatable, exclusive with --skip-section)'
            . PHP_EOL
            . '      --no-default-skip   Disable default skip lists (only user-specified values apply)' . PHP_EOL
            . '                          Default schemas: information_schema, pg_catalog, pg_toast' . PHP_EOL
            . '                          Default temporary schemas: pg_temp_*, pg_toast_temp_*' . PHP_EOL
            . '                          Default extensions: plpgsql' . PHP_EOL . PHP_EOL
            . '      --help              Print this help message' . PHP_EOL
            . '      --version           Print version information';
    }
}
