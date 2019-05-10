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
use SchemaKeeper\Worker\DeployedFunctions;
use SchemaKeeper\Worker\Saver;
use SchemaKeeper\Worker\Verifier;

/**
 * @api
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
        $this->saver->save($destinationPath);
    }

    /**
     * Compare current dump with dump previously saved in filesystem.
     * @param string $dumpPath Path to previously saved dump
     * @throws Exception
     */
    public function verifyDump($dumpPath)
    {
        $this->verifier->verify($dumpPath);
    }

    /**
     * Deploy functions from dump previously saved in filesystem.
     * @param string $dumpPath Path to previously saved dump
     * @return DeployedFunctions
     * @throws Exception
     */
    public function deployDump($dumpPath)
    {
        return $this->deployer->deploy($dumpPath);
    }
}
