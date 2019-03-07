<?php

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

    public function compare(Dump $expectedDump, Dump $actualDump)
    {
        $diff = [
            'left' => [],
            'right' => [],
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
