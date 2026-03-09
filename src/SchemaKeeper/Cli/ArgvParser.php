<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Cli;

use SchemaKeeper\Dto\{Parameters, Request, Section};
use SchemaKeeper\Exception\KeeperException;

final class ArgvParser
{
    private const LONG_OPTIONS = [
        'url',
        'host',
        'port',
        'dbname',
        'username',
        'password',
        'skip-schema',
        'skip-extension',
        'skip-section',
        'only-schema',
        'only-section',
        'no-default-skip',
        'help',
        'version',
    ];

    private const BOOLEAN_OPTIONS = ['no-default-skip', 'help', 'version'];

    private const SHORT_TO_LONG = [
        'h' => 'host',
        'p' => 'port',
        'd' => 'dbname',
        'U' => 'username',
    ];

    private CredentialsFactory $credentialsFactory;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(CredentialsFactory $credentialsFactory)
    {
        $this->credentialsFactory = $credentialsFactory;
    }

    public function parse(array $argv): Request
    {
        [$options, $flags, $positional] = $this->parseArgv($argv);

        $credentials = $this->credentialsFactory->createFromOptions($options);

        $command = $positional[0] ?? null;
        $dumpDir = $positional[1] ?? null;

        if (!$command) {
            throw new KeeperException('Command not specified');
        }

        if (!$dumpDir) {
            throw new KeeperException('Dump directory not specified');
        }

        $noDefaultSkip = isset($flags['no-default-skip']);
        $skipSchemas = $options['skip-schema'] ?? [];
        $skipExtensions = $options['skip-extension'] ?? [];
        $skipSections = $options['skip-section'] ?? [];
        $onlySchemas = $options['only-schema'] ?? [];
        $onlySections = $options['only-section'] ?? [];

        if ($onlySchemas && $skipSchemas) {
            throw new KeeperException(
                'Options --only-schema and --skip-schema are mutually exclusive',
            );
        }

        if ($onlySections && $skipSections) {
            throw new KeeperException(
                'Options --only-section and --skip-section are mutually exclusive',
            );
        }

        $validSections = Section::all();

        foreach ($skipSections as $value) {
            if (!in_array($value, $validSections, true)) {
                throw new KeeperException(
                    'Invalid --skip-section value: "' . $value . '".'
                    . ' Valid values: ' . implode(', ', $validSections),
                );
            }
        }

        if ($onlySections) {
            foreach ($onlySections as $value) {
                if (!in_array($value, $validSections, true)) {
                    throw new KeeperException(
                        'Invalid --only-section value: "' . $value . '".'
                        . ' Valid values: ' . implode(', ', $validSections),
                    );
                }
            }

            $skipSections = Section::allExcept($onlySections);
        }

        if ($noDefaultSkip) {
            $params = new Parameters(
                $skipSchemas,
                $skipExtensions,
                $skipSections,
                false,
                $onlySchemas,
            );
        } else {
            $params = new Parameters(
                self::mergeWithDefaults(
                    $onlySchemas ? [] : Parameters::DEFAULT_SKIPPED_SCHEMAS,
                    $skipSchemas,
                ),
                self::mergeWithDefaults(Parameters::DEFAULT_SKIPPED_EXTENSIONS, $skipExtensions),
                $skipSections,
                true,
                $onlySchemas,
            );
        }

        return new Request($credentials, $params, $command, $dumpDir);
    }

    private static function mergeWithDefaults(array $defaults, array $extra): array
    {
        return array_values(array_unique(array_merge($defaults, $extra)));
    }

    private function parseArgv(array $argv): array
    {
        $options = [];
        $flags = [];
        $positional = [];

        for ($i = 1, $count = count($argv); $i < $count; $i++) {
            $arg = $argv[$i];

            if ($arg === '--') {
                for ($j = $i + 1; $j < $count; $j++) {
                    $positional[] = $argv[$j];
                }

                break;
            }

            if (strpos($arg, '--') === 0) {
                $nameValue = substr($arg, 2);
                $eqPos = strpos($nameValue, '=');

                if ($eqPos !== false) {
                    $name = substr($nameValue, 0, $eqPos);
                    $value = substr($nameValue, $eqPos + 1);
                } else {
                    $name = $nameValue;
                    $value = null;
                }

                if (!in_array($name, self::LONG_OPTIONS, true)) {
                    throw new KeeperException('Unrecognized option: ' . $name);
                }

                if (in_array($name, self::BOOLEAN_OPTIONS, true)) {
                    if ($value !== null) {
                        throw new KeeperException('Option --' . $name . ' does not accept a value');
                    }

                    $flags[$name] = true;

                    continue;
                }

                if ($value === null) {
                    if ($i + 1 >= $count) {
                        throw new KeeperException('Option --' . $name . ' requires a value');
                    }

                    $i++;
                    $value = $argv[$i];
                }

                $options[$name][] = $value;

                continue;
            }

            if (strlen($arg) === 2 && $arg[0] === '-') {
                $shortFlag = $arg[1];

                if (!isset(self::SHORT_TO_LONG[$shortFlag])) {
                    throw new KeeperException('Unrecognized option: ' . $arg);
                }

                $name = self::SHORT_TO_LONG[$shortFlag];

                if ($i + 1 >= $count) {
                    throw new KeeperException('Option ' . $arg . ' requires a value');
                }

                $i++;
                $options[$name][] = $argv[$i];

                continue;
            }

            if ($arg[0] === '-' && strlen($arg) > 1) {
                throw new KeeperException('Unrecognized option: ' . $arg);
            }

            $positional[] = $arg;
        }

        return [$options, $flags, $positional];
    }
}
