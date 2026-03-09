<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper;

use SchemaKeeper\Comparator\DumpComparator;
use SchemaKeeper\Exception\{KeeperException, NotEquals};
use SchemaKeeper\Collector\Dumper;
use SchemaKeeper\Filesystem\{DumpReader, DumpWriter};

final class Keeper
{
    private Dumper $dumper;

    private DumpWriter $dumpWriter;

    private DumpReader $dumpReader;

    private DumpComparator $comparator;

    public function __construct(
        Dumper $dumper,
        DumpWriter $dumpWriter,
        DumpReader $dumpReader,
        DumpComparator $comparator
    ) {
        $this->dumper = $dumper;
        $this->dumpWriter = $dumpWriter;
        $this->dumpReader = $dumpReader;
        $this->comparator = $comparator;
    }

    public function saveDump(string $destinationPath): void
    {
        $dump = $this->dumper->dump();

        if (!$dump->getSchemas()) {
            throw new KeeperException('Dump is empty: no schemas to save');
        }

        $this->dumpWriter->write($destinationPath, $dump);
    }

    public function verifyDump(string $dumpPath): void
    {
        $actual = $this->dumper->dump();
        $expected = $this->dumpReader->read($dumpPath);

        $comparisonResult = $this->comparator->compare($expected, $actual);

        if (!empty($comparisonResult['expected']) || !empty($comparisonResult['actual'])) {
            throw new NotEquals(
                'Dump and current database are not equal',
                $comparisonResult['expected'],
                $comparisonResult['actual'],
            );
        }
    }
}
