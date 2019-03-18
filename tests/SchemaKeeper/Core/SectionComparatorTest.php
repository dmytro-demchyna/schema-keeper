<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

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
            'expected' => [
                'tables' => [
                    'test2' => "test_content2",
                ],
            ],
            'actual' => [
                'tables' => [
                    'test2' => "test_content",
                ],
            ],
        ];

        $actual = $this->target->compareSection($sectionName, $leftContent, $rightContent);

        self::assertEquals($expected, $actual);
    }
}
