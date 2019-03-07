<?php

namespace SchemaKeeper\Tests\Core;

use Mockery\MockInterface;
use SchemaKeeper\Core\ArrayConverter;
use SchemaKeeper\Core\Dump;
use SchemaKeeper\Core\DumpComparator;
use SchemaKeeper\Core\SectionComparator;
use SchemaKeeper\Tests\SchemaTestCase;

class DumpComparatorTest extends SchemaTestCase
{
    /**
     * @var DumpComparator
     */
    private $target;

    /**
     * @var ArrayConverter|MockInterface
     */
    private $converter;

    /**
     * @var SectionComparator|MockInterface
     */
    private $sectionComparator;

    public function setUp()
    {
        parent::setUp();

        $this->converter = \Mockery::mock(ArrayConverter::class);
        $this->sectionComparator = \Mockery::mock(SectionComparator::class);
        $this->target = new DumpComparator($this->converter, $this->sectionComparator);
    }

    public function testOk()
    {
        $dump1 = new Dump([], ['ext1']);
        $dump2 = new Dump([], ['ext2']);

        $this->converter->shouldReceive('dump2Array')->with($dump1)->andReturn([
            'tables' => ['table1'],
            'functions' => ['function1'],
        ])->once();

        $this->converter->shouldReceive('dump2Array')->with($dump2)->andReturn([
            'tables' => ['table2'],
            'functions' => ['function2'],
        ])->once();

        $this->sectionComparator->shouldReceive('compareSection')->with('tables', ['table1'], ['table2'])->andReturn([
            'left' => 'left1',
            'right' => 'right1',
        ])->once();

        $this->sectionComparator->shouldReceive('compareSection')->with('functions', ['function1'], ['function2'])->andReturn([
            'left' => 'left2',
            'right' => 'right2',
        ])->once();

        $expected = [
            'left' => [
                'left1',
                'left2',
            ],
            'right' => [
                'right1',
                'right2',
            ],
        ];

        $actual = $this->target->compare($dump1, $dump2);

        self::assertEquals($expected, $actual);
    }
}
