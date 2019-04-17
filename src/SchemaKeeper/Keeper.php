<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper;

use Exception;
use PDO;
use SchemaKeeper\Core\DumpEntryPoint;
use SchemaKeeper\Core\SyncEntryPoint;
use SchemaKeeper\Core\TestEntryPoint;
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;

/**
 * @api
 * @author Dmytro Demchyna <dmitry.demchina@gmail.com>
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
            throw new KeeperException('Only pgsql driver is supported');
        }

        $this->dumpEntryPoint = new DumpEntryPoint($conn, $parameters);
        $this->syncEntryPoint = new SyncEntryPoint($conn, $parameters);
        $this->testEntryPoint = new TestEntryPoint($conn, $parameters);
    }

    /**
     * Make structure dump from current database and save it in filesystem
     * @param string $destinationPath Dump will be saved in this folder
     * @throws Exception
     */
    public function saveDump($destinationPath)
    {
        $this->dumpEntryPoint->execute($destinationPath);
    }

    /**
     * Compare current dump with dump previously saved in filesystem.
     * Function returns array with keys: 'expected', 'actual'.
     * If 'expected' != 'actual' - the current database structure is different from the saved one.
     *
     * @param string $dumpPath Path to previously saved dump
     * @return array
     * @throws Exception
     */
    public function verifyDump($dumpPath)
    {
        return $this->testEntryPoint->execute($dumpPath);
    }

    /**
     * Deploy functions from dump previously saved in filesystem.
     * Function returns array with keys: 'expected', 'actual', 'deleted', 'created', 'changed'
     * If 'expected' != 'actual' - there is a problem with the files containing the source code of the stored procedures
     * 'deleted' - list of functions that were deleted from the current database, as they do not exist in the saved dump
     * 'created' - list of functions that were created in the current database, as they do not exist in the saved dump
     * 'changed' - list of functions that were changed in the current database, as their source code is different between saved dump and current database
     * @param string $dumpPath Path to previously saved dump
     * @return array
     * @throws Exception
     */
    public function deployDump($dumpPath)
    {
        return $this->syncEntryPoint->execute($dumpPath);
    }
}
