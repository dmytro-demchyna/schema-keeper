<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Worker;

use Exception;
use SchemaKeeper\Core\ArrayConverter;
use SchemaKeeper\Core\SectionComparator;
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Filesystem\DumpReader;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionReader;
use SchemaKeeper\Provider\IProvider;

class Deployer
{
    /**
     * @var DumpReader
     */
    private $reader;

    /**
     * @var IProvider
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
     * @param IProvider $provider
     */
    public function __construct(IProvider $provider)
    {
        $helper = new FilesystemHelper();
        $sectionReader = new SectionReader($helper);
        $this->reader = new DumpReader($sectionReader, $helper);
        $this->converter = new ArrayConverter();
        $this->comparator = new SectionComparator();
        $this->provider = $provider;
    }

    /**
     * @param string $sourcePath
     * @return array
     * @throws Exception
     */
    public function execute($sourcePath)
    {
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

        try
        {
            foreach ($functionNamesToDelete as $nameToDelete) {
                $lastExecutedName = $nameToDelete;
                $this->provider->deleteFunction($nameToDelete);

                unset($functionsToChange[$nameToDelete]);
            }

            foreach ($functionNamesToCreate as $nameToCreate) {
                $lastExecutedName = $nameToCreate;
                $functionContent = $expectedFunctions[$nameToCreate];
                $this->provider->createFunction($functionContent);

                unset($functionsToChange[$nameToCreate]);
            }

            foreach ($functionsToChange as $nameToChange => $contentToChange) {
                $lastExecutedName = $nameToChange;
                $this->provider->changeFunction($nameToChange, $contentToChange);
            }
        } catch (\PDOException $e) {
            $keeperException = new KeeperException("TARGET: $lastExecutedName\n".$e->getMessage(), 0, $e);

            throw $keeperException;
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
