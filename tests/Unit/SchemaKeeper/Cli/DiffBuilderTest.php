<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Cli;

use SchemaKeeper\Cli\DiffBuilder;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class DiffBuilderTest extends UnitTestCase
{
    private DiffBuilder $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new DiffBuilder();
    }

    public function testChanged(): void
    {
        $expected = [
            'functions' => [
                'public.my_func(integer)' => "line1\nline2\nline3",
            ],
        ];
        $actual = [
            'functions' => [
                'public.my_func(integer)' => "line1\nchanged\nline3",
            ],
        ];

        $result = $this->target->format($expected, $actual);

        $expectedOutput = '--- functions/public.my_func(integer)' . PHP_EOL
            . '+++ functions/public.my_func(integer)' . PHP_EOL
            . '@@ @@' . PHP_EOL
            . ' line1' . PHP_EOL
            . '-line2' . PHP_EOL
            . '+changed' . PHP_EOL
            . ' line3';

        self::assertEquals($expectedOutput, $result);
    }

    public function testRemoved(): void
    {
        $expected = [
            'triggers' => [
                'public.test_table.my_trigger' => 'CREATE TRIGGER my_trigger BEFORE UPDATE ON test_table',
            ],
        ];
        $actual = [];

        $result = $this->target->format($expected, $actual);

        $expectedOutput = '--- triggers/public.test_table.my_trigger' . PHP_EOL
            . '+++ /dev/null' . PHP_EOL
            . '@@ @@' . PHP_EOL
            . '-CREATE TRIGGER my_trigger BEFORE UPDATE ON test_table';

        self::assertEquals($expectedOutput, $result);
    }

    public function testAdded(): void
    {
        $expected = [];
        $actual = [
            'triggers' => [
                'public.test_table.my_trigger' => 'CREATE TRIGGER my_trigger BEFORE UPDATE ON test_table',
            ],
        ];

        $result = $this->target->format($expected, $actual);

        $expectedOutput = '--- /dev/null' . PHP_EOL
            . '+++ triggers/public.test_table.my_trigger' . PHP_EOL
            . '@@ @@' . PHP_EOL
            . '+CREATE TRIGGER my_trigger BEFORE UPDATE ON test_table';

        self::assertEquals($expectedOutput, $result);
    }

    public function testEmpty(): void
    {
        $result = $this->target->format([], []);

        self::assertEquals('', $result);
    }

    public function testMixedSections(): void
    {
        $expected = [
            'functions' => [
                'public.old_func()' => 'CREATE FUNCTION old_func()',
            ],
        ];
        $actual = [
            'triggers' => [
                'public.test_table.new_trigger' => 'CREATE TRIGGER new_trigger',
            ],
        ];

        $result = $this->target->format($expected, $actual);

        $expectedOutput = '--- functions/public.old_func()' . PHP_EOL
            . '+++ /dev/null' . PHP_EOL
            . '@@ @@' . PHP_EOL
            . '-CREATE FUNCTION old_func()' . PHP_EOL
            . '--- /dev/null' . PHP_EOL
            . '+++ triggers/public.test_table.new_trigger' . PHP_EOL
            . '@@ @@' . PHP_EOL
            . '+CREATE TRIGGER new_trigger';

        self::assertEquals($expectedOutput, $result);
    }
}
