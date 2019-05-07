<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper;

use Exception;
use PDO;
use SchemaKeeper\Provider\ProviderFactory;
use SchemaKeeper\Worker\Deployer;
use SchemaKeeper\Worker\Saver;
use SchemaKeeper\Worker\Verifier;

/**
 * @api
 * @author Dmytro Demchyna <dmitry.demchina@gmail.com>
 */
class Keeper
{
    /**
     * @var Saver
     */
    private $saver;

    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var Verifier
     */
    private $verifier;

    /**
     * @param PDO $conn
     * @param object $parameters Depends on DBMS. Only PSQLParameters supported now (PostgreSQL)
     * @throws Exception
     * @see \SchemaKeeper\Provider\PostgreSQL\PSQLParameters
     */
    public function __construct(PDO $conn, $parameters = null)
    {
        $factory = new ProviderFactory();
        $provider = $factory->createProvider($conn, $parameters);

        $this->saver = new Saver($provider);
        $this->deployer = new Deployer($provider);
        $this->verifier = new Verifier($provider);
    }

    /**
     * Make structure dump from current database and save it in filesystem
     * @param string $destinationPath Dump will be saved in this folder
     * @throws Exception
     */
    public function saveDump($destinationPath)
    {
        $this->saver->execute($destinationPath);
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
        return $this->verifier->execute($dumpPath);
    }

    /**
     * Deploy functions from dump previously saved in filesystem.
     * Function returns array with keys: 'deleted', 'created', 'changed'
     * 'deleted' - list of functions that were deleted from the current database, as they do not exist in the saved dump
     * 'created' - list of functions that were created in the current database, as they do not exist in the saved dump
     * 'changed' - list of functions that were changed in the current database, as their source code is different between saved dump and current database
     * @param string $dumpPath Path to previously saved dump
     * @return array
     * @throws Exception
     */
    public function deployDump($dumpPath)
    {
        return $this->deployer->execute($dumpPath);
    }
}
