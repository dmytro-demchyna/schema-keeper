<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Core;

use Exception;
use PDO;
use SchemaKeeper\Filesystem\DumpWriter;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionWriter;
use SchemaKeeper\Provider\PostgreSQL\PSQLClient;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;
use SchemaKeeper\Provider\PostgreSQL\PSQLProvider;

class DumpEntryPoint
{
    /**
     * @var Dumper
     */
    private $dumper;

    /**
     * @var DumpWriter
     */
    private $writer;

    /**
     * @param PDO $conn
     * @param PSQLParameters $parameters
     * @throws Exception
     */
    public function __construct(PDO $conn, PSQLParameters $parameters)
    {
        $client = new PSQLClient(
            $parameters->getDbName(),
            $parameters->getHost(),
            $parameters->getPort(),
            $parameters->getUser(),
            $parameters->getPassword()
        );
        $provider = new PSQLProvider($conn, $client, $parameters->getSkippedSchemaNames(), $parameters->getSkippedExtensionNames());

        $schemaFilter = new SchemaFilter();
        $this->dumper = new Dumper($provider, $schemaFilter);
        $helper = new FilesystemHelper();
        $sectionWriter = new SectionWriter($helper);
        $this->writer = new DumpWriter($sectionWriter, $helper);
    }

    /**
     * @param string $destinationPath
     * @throws Exception
     */
    public function execute($destinationPath)
    {
        $dump = $this->dumper->dump();
        $this->writer->write($destinationPath, $dump);
    }
}
