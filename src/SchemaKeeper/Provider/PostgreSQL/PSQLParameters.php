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
     * @var string[]
     */
    private $skippedSchemaNames = [];

    /**
     * @var string[]
     */
    private $skippedExtensionNames = [];


    public function __construct(string $host, int $port, string $dbName, string $user, string $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->dbName = $dbName;
        $this->user = $user;
        $this->password = $password;
    }

    public function getDbName(): string
    {
        return $this->dbName;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getExecutable(): string
    {
        return $this->executable;
    }

    public function setExecutable(string $executable): void
    {
        $this->executable = $executable;
    }

    public function getSkippedSchemas(): array
    {
        return $this->skippedSchemaNames;
    }

    public function setSkippedSchemas(array $skippedSchemaNames): void
    {
        $this->skippedSchemaNames = $skippedSchemaNames;
    }

    public function getSkippedExtensions(): array
    {
        return $this->skippedExtensionNames;
    }

    public function setSkippedExtensions(array $skippedExtensionNames): void
    {
        $this->skippedExtensionNames = $skippedExtensionNames;
    }
}
