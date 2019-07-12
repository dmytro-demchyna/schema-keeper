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

    public function __construct(PSQLParameters $params, string $command, string $path)
    {
        $this->params = $params;
        $this->command = $command;
        $this->path = $path;
    }

    public function getParams(): PSQLParameters
    {
        return $this->params;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
