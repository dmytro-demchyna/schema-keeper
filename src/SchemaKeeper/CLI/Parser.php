<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\CLI;

use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

class Parser
{
    public function parse(array $options, array $argv): Parsed
    {
        $configPath = isset($options['c']) ? $options['c'] : null;

        if (!$configPath || !is_readable($configPath)) {
            throw new KeeperException("Config file not found or not readable ".$configPath);
        }

        $params = require $configPath;

        if (!($params instanceof PSQLParameters)) {
            throw new KeeperException("Config file must return instance of ".PSQLParameters::class);
        }

        $path = isset($options['d']) ? $options['d'] : null;

        if (!$path) {
            throw new KeeperException("Destination path not specified");
        }

        $count = count($argv);
        $command = isset($argv[$count - 1]) ? $argv[$count - 1] : null;

        return new Parsed($params, $command, $path);
    }
}
