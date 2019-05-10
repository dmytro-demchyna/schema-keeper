<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\CLI;

use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

class Parsed
{
    /**
     * @var PSQLParameters
     */
    private $params;

    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $path;

    /**
     * @param PSQLParameters $params
     * @param string $command
     * @param string $path
     */
    public function __construct(PSQLParameters $params, $command, $path)
    {
        $this->params = $params;
        $this->command = $command;
        $this->path = $path;
    }

    /**
     * @return PSQLParameters
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
