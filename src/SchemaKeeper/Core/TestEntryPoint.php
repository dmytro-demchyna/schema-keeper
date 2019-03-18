<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Core;

use Exception;
use PDO;
use SchemaKeeper\Filesystem\DumpReader;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionReader;
use SchemaKeeper\Provider\PostgreSQL\PSQLClient;
use SchemaKeeper\Provider\PostgreSQL\PSQLParameters;
use SchemaKeeper\Provider\PostgreSQL\PSQLProvider;

class TestEntryPoint
{
    /**
     * @var Dumper
     */
    private $dumper;

    /**
     * @var DumpReader
     */
    private $dumpReader;

    /**
     * @var DumpComparator
     */
    private $comparator;

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

        $provider = new PSQLProvider(
            $conn,
            $client,
            $parameters->getSkippedSchemaNames(),
            $parameters->getSkippedExtensionNames()
        );

        $schemaFilter = new SchemaFilter();
        $this->dumper = new Dumper($provider, $schemaFilter);

        $helper = new FilesystemHelper();
        $sectionReader = new SectionReader($helper);
        $this->dumpReader = new DumpReader($sectionReader, $helper);
        $converter = new ArrayConverter();
        $sectionComparator = new SectionComparator();
        $this->comparator = new DumpComparator($converter, $sectionComparator);
    }

    /**
     * @param string $sourcePath
     * @return array
     * @throws Exception
     */
    public function execute($sourcePath)
    {
        $actual = $this->dumper->dump();
        $expected = $this->dumpReader->read($sourcePath);

        $result = $this->comparator->compare($expected, $actual);

        return $result;
    }
}
