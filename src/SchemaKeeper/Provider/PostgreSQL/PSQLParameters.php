<?php

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
     * @return array
     */
    public function getSkippedSchemaNames()
    {
        return $this->skippedSchemaNames;
    }

    /**
     * @param array $skippedSchemaNames
     */
    public function setSkippedSchemaNames(array $skippedSchemaNames)
    {
        $this->skippedSchemaNames = $skippedSchemaNames;
    }

    /**
     * @return array
     */
    public function getSkippedExtensionNames()
    {
        return $this->skippedExtensionNames;
    }

    /**
     * @param array $skippedExtensionNames
     */
    public function setSkippedExtensionNames(array $skippedExtensionNames)
    {
        $this->skippedExtensionNames = $skippedExtensionNames;
    }
}
