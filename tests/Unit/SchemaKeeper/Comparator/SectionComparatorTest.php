<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Comparator;

use SchemaKeeper\Comparator\SectionComparator;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class SectionComparatorTest extends UnitTestCase
{
    private SectionComparator $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new SectionComparator();
    }

    public function testOk(): void
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
                    'test2' => 'test_content2',
                ],
            ],
            'actual' => [
                'tables' => [
                    'test2' => 'test_content',
                ],
            ],
        ];

        $actual = $this->target->compareSection($sectionName, $leftContent, $rightContent);

        self::assertEquals($expected, $actual);
    }

    public function testAddedItem(): void
    {
        $result = $this->target->compareSection(
            'tables',
            [],
            ['new_table' => 'new_def'],
        );

        self::assertEmpty($result['expected']);
        self::assertEquals(['tables' => ['new_table' => 'new_def']], $result['actual']);
    }

    public function testRemovedItem(): void
    {
        $result = $this->target->compareSection(
            'tables',
            ['old_table' => 'old_def'],
            [],
        );

        self::assertEquals(['tables' => ['old_table' => 'old_def']], $result['expected']);
        self::assertEmpty($result['actual']);
    }

    public function testBothEmpty(): void
    {
        $result = $this->target->compareSection('tables', [], []);

        self::assertEmpty($result);
    }

    public function testActualEmptyStringIncludedInDiff(): void
    {
        $leftContent = [
            'item1' => 'content1',
        ];

        $rightContent = [
            'item1' => '',
        ];

        $expected = [
            'expected' => [
                'functions' => [
                    'item1' => 'content1',
                ],
            ],
            'actual' => [
                'functions' => [
                    'item1' => '',
                ],
            ],
        ];

        $actual = $this->target->compareSection('functions', $leftContent, $rightContent);

        self::assertEquals($expected, $actual);
    }

    public function testMixedAddRemoveChangeInSameSection(): void
    {
        $result = $this->target->compareSection(
            'tables',
            ['users' => 'old_def', 'removed' => 'removed_def'],
            ['users' => 'new_def', 'added' => 'added_def'],
        );

        self::assertEquals(
            ['tables' => ['users' => 'old_def', 'removed' => 'removed_def']],
            $result['expected'],
        );
        self::assertEquals(
            ['tables' => ['users' => 'new_def', 'added' => 'added_def']],
            $result['actual'],
        );
    }
}
