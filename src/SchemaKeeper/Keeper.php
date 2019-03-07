<?php

namespace SchemaKeeper;

use Exception;
use PDO;
use SchemaKeeper\Core\DumpEntryPoint;
use SchemaKeeper\Core\SyncEntryPoint;
use SchemaKeeper\Core\TestEntryPoint;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

/**
 * @package SchemaKeeper
 * @api
 */
class Keeper
{
    /**
     * @var DumpEntryPoint
     */
    private $dumpEntryPoint;

    /**
     * @var SyncEntryPoint
     */
    private $syncEntryPoint;

    /**
     * @var TestEntryPoint
     */
    private $testEntryPoint;

    /**
     * @param PDO $conn
     * @param PSQLParameters $parameters
     * @throws Exception
     */
    public function __construct(PDO $conn, PSQLParameters $parameters)
    {
        if ($conn->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
            throw new Exception('Only pgsql driver is supported');
        }

        $this->dumpEntryPoint = new DumpEntryPoint($conn, $parameters);
        $this->syncEntryPoint = new SyncEntryPoint($conn, $parameters);
        $this->testEntryPoint = new TestEntryPoint($conn, $parameters);
    }

    /**
     * Make structure dump from current database and save it in filesystem
     * @param string $destinationPath
     * @throws Exception
     */
    public function writeDump($destinationPath)
    {
        $this->dumpEntryPoint->execute($destinationPath);
    }

    /**
     * Compare current dump with dump previously saved in filesystem
     * @param string $dumpPath
     * @return array
     * @throws Exception
     */
    public function verifyDump($dumpPath)
    {
        return $this->testEntryPoint->execute($dumpPath);
    }

    /**
     * Deploy functions from dump previously saved in filesystem
     * @param $dumpPath
     * @return array
     * @throws Exception
     */
    public function deployDump($dumpPath)
    {
        return $this->syncEntryPoint->execute($dumpPath);
    }
}
