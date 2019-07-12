<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Core;

class DumpComparator
{
    /**
     * @var ArrayConverter
     */
    private $converter;

    /**
     * @var SectionComparator
     */
    private $sectionComparator;

    public function __construct(ArrayConverter $converter, SectionComparator $sectionComparator)
    {
        $this->converter = $converter;
        $this->sectionComparator = $sectionComparator;
    }

    /**
     * @param Dump $expectedDump
     * @param Dump $actualDump
     * @return array{expected:array,actual:array}
     */
    public function compare(Dump $expectedDump, Dump $actualDump): array
    {
        $diff = [
            'expected' => [],
            'actual' => [],
        ];

        $expectedArray = $this->converter->dump2Array($expectedDump);
        $actualArray = $this->converter->dump2Array($actualDump);

        $keys = array_keys($expectedArray);

        foreach ($keys as $key) {
            $diff = array_merge_recursive(
                $diff,
                $this->sectionComparator->compareSection($key, $expectedArray[$key], $actualArray[$key])
            );
        }

        return $diff;
    }
}
