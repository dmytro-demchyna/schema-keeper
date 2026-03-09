<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Comparator;

use SchemaKeeper\Dto\{Dump, Section};

final class DumpComparator
{
    private SectionComparator $sectionComparator;

    public function __construct(SectionComparator $sectionComparator)
    {
        $this->sectionComparator = $sectionComparator;
    }

    public function compare(Dump $expectedDump, Dump $actualDump): array
    {
        $diff = [
            'expected' => [],
            'actual' => [],
        ];

        $expectedArray = $this->dump2Array($expectedDump);
        $actualArray = $this->dump2Array($actualDump);

        $keys = array_unique(array_merge(array_keys($expectedArray), array_keys($actualArray)));

        foreach ($keys as $key) {
            $diff = array_merge_recursive(
                $diff,
                $this->sectionComparator->compareSection($key, $expectedArray[$key] ?? [], $actualArray[$key] ?? []),
            );
        }

        return $diff;
    }

    private function dump2Array(Dump $dump): array
    {
        $result = array_fill_keys(Section::all(), []);
        $result['schemas'] = [];
        $result['extensions'] = $dump->getExtensions();

        foreach ($dump->getSchemas() as $schemaDump) {
            $result['schemas'][$schemaDump->getSchemaName()] = $schemaDump->getSchemaName();

            foreach (Section::all() as $section) {
                $newItems = [];

                foreach ($schemaDump->getSection($section) as $itemName => $itemContent) {
                    $newItems[$schemaDump->getSchemaName() . '.' . $itemName] = $itemContent;
                }

                $result[$section] = array_merge($result[$section], $newItems);
            }
        }

        return $result;
    }
}
