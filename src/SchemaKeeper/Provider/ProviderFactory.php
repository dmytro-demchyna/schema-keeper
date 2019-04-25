<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Provider;

use PDO;
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Provider\PostgreSQL\PSQLClient;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;
use SchemaKeeper\Provider\PostgreSQL\PSQLProvider;

class ProviderFactory
{
    /**
     * @param PDO $conn
     * @param object $parameters
     * @return IProvider
     * @throws KeeperException
     */
    public function createProvider(PDO $conn, $parameters = null)
    {
        if ($conn->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
            throw new KeeperException('Only pgsql driver is supported');
        }

        if (!($parameters instanceof PSQLParameters)) {
            throw new KeeperException('$parameters must be instance of '.PSQLParameters::class);
        }

        $client = new PSQLClient(
            $parameters->getDbName(),
            $parameters->getHost(),
            $parameters->getPort(),
            $parameters->getUser(),
            $parameters->getPassword()
        );

        $provider = new PSQLProvider(
            $conn,
            $client,
            $parameters->getSkippedSchemaNames(),
            $parameters->getSkippedExtensionNames()
        );

        return $provider;
    }
}
