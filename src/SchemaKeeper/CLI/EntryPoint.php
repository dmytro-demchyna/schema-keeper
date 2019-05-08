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

class EntryPoint
{
    /**
     * @param array $options
     * @param array $argv
     * @return Result
     * @throws \Exception
     */
    public function run(array $options, array $argv)
    {
        if (isset($options['help'])) {
            $helpMessage = "Usage: schemakeeper [options] <command>

Example: schemakeeper -c /path_to_config.php -d /path_to_dump save

Options:
\t-c\tThe path to a config file
\t-d\tThe destination path to a dump directory

Available commands:
\tsave
\tverify
\tdeploy
";
            return new Result($helpMessage, 0);
        }

        try {
            $parser = new Parser();
            $parsed = $parser->parse($options, $argv);

            $params = $parsed->getParams();
            $path = $parsed->getPath();
            $command = $parsed->getCommand();

            $dsn = 'pgsql:dbname=' . $params->getDbName() . ';host=' . $params->getHost() . ';port=' . $params->getPort();
            $conn = new PDO($dsn, $params->getUser(), $params->getPassword(), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $keeper = new Keeper($conn, $params);
            $runner = new Runner($keeper);

            $result = $runner->run($command, $path);

            return new Result($result, 0);
        } catch (KeeperException $e) {
            return new Result($e->getMessage(), 1);
        }
    }
}
