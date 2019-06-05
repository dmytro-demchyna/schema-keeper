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
use SchemaKeeper\Exception\NotEquals;
use SchemaKeeper\Filesystem\DumpReader;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionReader;
use SchemaKeeper\Outside\DeployedFunctions;
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
     * @return DeployedFunctions
     * @throws Exception
     */
    public function deploy($sourcePath)
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

        if (count($functionNamesToDelete) == count($functions)
            && count($functionNamesToDelete) > 0
            && count($functionsToChange) == 0
            && count($functionNamesToCreate) == 0
        ) {
            throw new KeeperException('Forbidden to remove all functions using SchemaKeeper');
        }

        $lastExecutedName = null;

        try {
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
            $keeperException = new KeeperException("TARGET: $lastExecutedName; " . $e->getMessage(), 0, $e);

            throw $keeperException;
        }

        $actualFunctions = $this->provider->getFunctions();
        $comparisonResult = $this->comparator->compareSection('functions', $expectedFunctions, $actualFunctions);

        if ($comparisonResult['actual'] !== $comparisonResult['expected']) {
            $leftString = implode(', ', array_keys($comparisonResult['expected']['functions']));
            $rightString = implode(', ', array_keys($comparisonResult['actual']['functions']));
            $allString = $leftString . '; ' . $rightString . '.';

            $message = 'Some functions have diff between their definitions before deploy and their definitions after deploy: ' . $allString;

            throw new NotEquals($message, $comparisonResult['expected'], $comparisonResult['actual']);
        }

        return new DeployedFunctions(array_keys($functionsToChange), $functionNamesToCreate, $functionNamesToDelete);
    }
}
