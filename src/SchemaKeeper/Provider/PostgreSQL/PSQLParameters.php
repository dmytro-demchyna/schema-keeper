<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Provider\PostgreSQL;

class PSQLParameters
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $dbName;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $executable = 'psql';

    /**
     * @var array
     */
    private $skippedSchemaNames = [];

    /**
     * @var array
     */
    private $skippedExtensionNames = [];


    /**
     * @param string $host
     * @param int $port
     * @param string $dbName
     * @param string $user
     * @param string $password
     */
    public function __construct($host, $port, $dbName, $user, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->dbName = $dbName;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getExecutable()
    {
        return $this->executable;
    }

    /**
     * @param string $executable
     */
    public function setExecutable($executable)
    {
        $this->executable = $executable;
    }

    /**
     * @return array
     */
    public function getSkippedSchemas()
    {
        return $this->skippedSchemaNames;
    }

    /**
     * @param array $skippedSchemaNames
     */
    public function setSkippedSchemas(array $skippedSchemaNames)
    {
        $this->skippedSchemaNames = $skippedSchemaNames;
    }

    /**
     * @return array
     */
    public function getSkippedExtensions()
    {
        return $this->skippedExtensionNames;
    }

    /**
     * @param array $skippedExtensionNames
     */
    public function setSkippedExtensions(array $skippedExtensionNames)
    {
        $this->skippedExtensionNames = $skippedExtensionNames;
    }
}
