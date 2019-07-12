<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\CLI;

use PDO;
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Keeper;

/**
 * @internal
 */
class EntryPoint
{
    public function run(array $options, array $argv): Result
    {
        $message = '';

        foreach ($options as $optionName => $optionValue) {
            if (!in_array($optionName, ['c', 'd', 'help', 'version'])) {
                $message .= 'Unrecognized option: ' . $optionName;

                return new Result($message, 1);
            }
        }

        if (isset($options['help'])) {
            $message .= "Usage: schemakeeper [options] <command>" . PHP_EOL . PHP_EOL .
                'Example: schemakeeper -c /path_to_config.php -d /path_to_dump save' . PHP_EOL . PHP_EOL
                . 'Options:' . PHP_EOL
                . '  -c    The path to a config file' . PHP_EOL
                . '  -d    The destination path to a dump directory' . PHP_EOL . PHP_EOL
                . '  --help       Print this help message' . PHP_EOL
                . '  --version    Print version information' . PHP_EOL . PHP_EOL
                . 'Available commands:' . PHP_EOL
                . '  save' . PHP_EOL
                . '  verify' . PHP_EOL
                . '  deploy';

            return new Result($message, 0);
        }

        if (isset($options['version'])) {
            return new Result($message, 0);
        }

        try {
            $parser = new Parser();
            $parsed = $parser->parse($options, $argv);

            $params = $parsed->getParams();
            $path = $parsed->getPath();
            $command = $parsed->getCommand();

            $dsn = 'pgsql:dbname=' . $params->getDbName() . ';host=' . $params->getHost() . ';port=' . $params->getPort();
            $conn = new PDO(
                $dsn,
                $params->getUser(),
                $params->getPassword(),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $keeper = new Keeper($conn, $params);
            $runner = new Runner($keeper);

            $conn->beginTransaction();

            try {
                $result = $runner->run($command, $path);
                $message .= 'Success: ' . $result;
                $conn->commit();
            } catch (\Exception $exception) {
                $conn->rollBack();
                throw $exception;
            }

            return new Result($message, 0);
        } catch (KeeperException $e) {
            $message .= 'Failure: ' . $e->getMessage();
            return new Result($message, 1);
        }
    }
}
