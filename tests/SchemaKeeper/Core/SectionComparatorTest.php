<?php

namespace SchemaKeeper\Tests\Core;

use SchemaKeeper\Core\SectionComparator;
use SchemaKeeper\Tests\SchemaTestCase;

class SectionComparatorTest extends SchemaTestCase
{
    /**
     * @var SectionComparator
     */
    private $target;

    public function setUp()
    {
        parent::setUp();

        $this->target = new SectionComparator();
    }

    public function testOk()
    {
        $sectionName = 'tables';

        $leftContent = [
            'test1' => 'test_content1',
            'test2' => 'test_content2',
            'test3' => 'test_content3',
        ];

        $rightContent = [
            'test1' => 'test_content1',
            'test2' => 'test_content',
            'test3' => 'test_content3',
        ];

        $expected = [
            'left' => [
                'tables' => [
                    'test2' => "test_content2",
                ],
            ],
            'right' => [
                'tables' => [
                    'test2' => "test_content",
                ],
            ],
        ];

        $actual = $this->target->compareSection($sectionName, $leftContent, $rightContent);

        self::assertEquals($expected, $actual);
    }
}
