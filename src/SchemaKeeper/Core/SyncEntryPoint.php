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

class SyncEntryPoint
{
    /**
     * @var PDO
     */
    private $conn;

    /**
     * @var DumpReader
     */
    private $reader;

    /**
     * @var PSQLProvider
     */
    private $provider;

    /**
     * @var SectionComparator
     */
    private $comparator;

    /**
     * @var ArrayConverter
     */
    private $converter;

    /**
     * @var SavepointHelper
     */
    private $savepointHelper;

    /**
     * @param PDO $conn
     * @param PSQLParameters $parameters
     * @throws Exception
     */
    public function __construct(PDO $conn, PSQLParameters $parameters)
    {
        $this->conn = $conn;
        $helper = new FilesystemHelper();
        $sectionReader = new SectionReader($helper);
        $this->reader = new DumpReader($sectionReader, $helper);
        $this->converter = new ArrayConverter();
        $this->comparator = new SectionComparator();

        $client = new PSQLClient(
            $parameters->getDbName(),
            $parameters->getHost(),
            $parameters->getPort(),
            $parameters->getUser(),
            $parameters->getPassword()
        );

        $this->provider = new PSQLProvider(
            $conn,
            $client,
            $parameters->getSkippedSchemaNames(),
            $parameters->getSkippedExtensionNames()
        );

        $this->savepointHelper = new SavepointHelper($conn);
    }

    /**
     * @param string $sourcePath
     * @return array
     * @throws Exception
     */
    public function execute($sourcePath)
    {
        $conn = $this->conn;

        $functions = $this->provider->getFunctions();
        $actualFunctionNames = array_keys($functions);

        $structurePath = $sourcePath;
        $expectedDump = $this->reader->read($structurePath);
        $expectedFunctions = $this->converter->dump2Array($expectedDump)['functions'];
        $expectedFunctionNames = array_keys($expectedFunctions);

        $functionNamesToCreate = array_diff($expectedFunctionNames, $actualFunctionNames);
        $functionNamesToDelete = array_diff($actualFunctionNames, $expectedFunctionNames);
        $functionsToChange = array_diff_assoc($expectedFunctions, $functions);

        $lastExecutedName = null;

        try {
            foreach ($functionNamesToDelete as $nameToDelete) {
                $lastExecutedName = $nameToDelete;
                $sqlDelete = 'DROP FUNCTION ' . $nameToDelete;
                $conn->exec($sqlDelete);

                unset($functionsToChange[$nameToDelete]);
            }

            foreach ($functionNamesToCreate as $nameToCreate) {
                $lastExecutedName = $nameToCreate;
                $functionContent = $expectedFunctions[$nameToCreate];
                $conn->exec($functionContent);

                unset($functionsToChange[$nameToCreate]);
            }

            foreach ($functionsToChange as $nameToChange => $contentToChange) {
                try {
                    $lastExecutedName = $nameToChange;
                    $this->savepointHelper->beginTransaction('before_change');
                    $conn->exec($contentToChange);
                    $this->savepointHelper->commit('before_change');
                } catch (Exception $e) {
                    $this->savepointHelper->rollback('before_change');
                    $conn->exec('DROP FUNCTION ' . $nameToChange);
                    $conn->exec($contentToChange);
                }
            }
        }
        catch (\PDOException $e) {
            $extendedException = new \PDOException($e->getMessage()."\nTARGET: $lastExecutedName", $e->getCode(), $e);

            throw $extendedException;
        }

        $actualFunctions = $this->provider->getFunctions();
        $comparisonResult = $this->comparator->compareSection('functions', $expectedFunctions, $actualFunctions);

        return [
            'expected' => $comparisonResult['expected'],
            'actual' => $comparisonResult['actual'],
            'deleted' => $functionNamesToDelete,
            'created' => $functionNamesToCreate,
            'changed' => array_keys($functionsToChange),
        ];
    }
}
