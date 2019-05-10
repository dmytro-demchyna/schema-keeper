<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Worker;

use Exception;
use SchemaKeeper\Core\ArrayConverter;
use SchemaKeeper\Core\DumpComparator;
use SchemaKeeper\Core\Dumper;
use SchemaKeeper\Core\SchemaFilter;
use SchemaKeeper\Core\SectionComparator;
use SchemaKeeper\Exception\NotEquals;
use SchemaKeeper\Filesystem\DumpReader;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionReader;
use SchemaKeeper\Provider\IProvider;

class Verifier
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
     * @param IProvider $provider
     */
    public function __construct(IProvider $provider)
    {
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
     * @throws Exception
     */
    public function verify($sourcePath)
    {
        $actual = $this->dumper->dump();
        $expected = $this->dumpReader->read($sourcePath);

        $comparisonResult = $this->comparator->compare($expected, $actual);

        if ($comparisonResult['expected'] !== $comparisonResult['actual']) {
            throw new NotEquals('Dump and current database not equals', $comparisonResult['expected'], $comparisonResult['actual']);
        }
    }
}
