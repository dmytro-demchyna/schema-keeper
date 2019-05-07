<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\CLI;

use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

class Parser
{
    /**
     * @param $options
     * @param $argv
     * @return Parsed
     * @throws \Exception
     */
    public function parse($options, $argv)
    {
        $configPath = isset($options['c']) ? $options['c'] : null;

        if (!$configPath || !is_readable($configPath)) {
            throw new \Exception("Config file not found or not readable ".$configPath);
        }

        $params = require_once $configPath;

        if (!($params instanceof PSQLParameters)) {
            throw new \Exception("Config file must return instance of ".PSQLParameters::class);
        }

        $path = isset($options['d']) ? $options['d'] : null;

        if (!$path) {
            throw new \Exception("Destination path not specified");
        }

        $count = count($argv);
        $command = isset($argv[$count - 1]) ? $argv[$count - 1] : null;

        return new Parsed($params, $command, $path);
    }
}
