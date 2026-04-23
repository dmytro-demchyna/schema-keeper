<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Comparator;

use SchemaKeeper\Dto\{Dump, SchemaDump, Section};
use SchemaKeeper\Comparator\{DumpComparator, SectionComparator};
use SchemaKeeper\Tests\Unit\UnitTestCase;

class DumpComparatorTest extends UnitTestCase
{
    private DumpComparator $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new DumpComparator(new SectionComparator());
    }

    public function testOk(): void
    {
        $dump1 = new Dump(
            [new SchemaDump('public', [
                Section::TABLES => ['users' => 'old_def'],
                Section::FUNCTIONS => ['func' => 'old_func'],
            ])],
            ['ext1' => 'public'],
        );

        $dump2 = new Dump(
            [new SchemaDump('public', [
                Section::TABLES => ['users' => 'new_def'],
                Section::FUNCTIONS => ['func' => 'new_func'],
            ])],
            ['ext2' => 'public'],
        );

        $result = $this->target->compare($dump1, $dump2);

        $expected = [
            'expected' => [
                'tables' => ['public.users' => 'old_def'],
                'functions' => ['public.func' => 'old_func'],
                'extensions' => ['ext1' => 'public'],
            ],
            'actual' => [
                'tables' => ['public.users' => 'new_def'],
                'functions' => ['public.func' => 'new_func'],
                'extensions' => ['ext2' => 'public'],
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testActualHasExtraKey(): void
    {
        $dump1 = new Dump(
            [new SchemaDump('public', [
                Section::TABLES => ['users' => 'def'],
            ])],
            [],
        );

        $dump2 = new Dump(
            [new SchemaDump('public', [
                Section::TABLES => ['users' => 'def'],
                Section::VIEWS => ['my_view' => 'view_def'],
            ])],
            [],
        );

        $result = $this->target->compare($dump1, $dump2);

        self::assertEmpty($result['expected']);
        self::assertEquals(['views' => ['public.my_view' => 'view_def']], $result['actual']);
    }

    public function testCompareWithDifferences(): void
    {
        $dump1 = new Dump(
            [new SchemaDump('public', [
                Section::TABLES => ['users' => 'old_def', 'posts' => 'same_def'],
            ])],
            [],
        );

        $dump2 = new Dump(
            [new SchemaDump('public', [
                Section::TABLES => ['users' => 'new_def', 'posts' => 'same_def'],
            ])],
            [],
        );

        $result = $this->target->compare($dump1, $dump2);

        self::assertEquals(['tables' => ['public.users' => 'old_def']], $result['expected']);
        self::assertEquals(['tables' => ['public.users' => 'new_def']], $result['actual']);
    }

    public function testMixedAddRemoveChangeInSameSection(): void
    {
        $dump1 = new Dump(
            [new SchemaDump('public', [
                Section::TABLES => ['users' => 'old_def', 'removed' => 'removed_def'],
            ])],
            [],
        );

        $dump2 = new Dump(
            [new SchemaDump('public', [
                Section::TABLES => ['users' => 'new_def', 'added' => 'added_def'],
            ])],
            [],
        );

        $result = $this->target->compare($dump1, $dump2);

        self::assertEquals(
            ['tables' => ['public.users' => 'old_def', 'public.removed' => 'removed_def']],
            $result['expected'],
        );
        self::assertEquals(
            ['tables' => ['public.users' => 'new_def', 'public.added' => 'added_def']],
            $result['actual'],
        );
    }

    public function testSchemaMismatch(): void
    {
        $dump1 = new Dump(
            [
                new SchemaDump('public', [
                    Section::TABLES => ['users' => 'def'],
                ]),
                new SchemaDump('auth', [
                    Section::TABLES => ['tokens' => 'token_def'],
                ]),
            ],
            [],
        );

        $dump2 = new Dump(
            [new SchemaDump('public', [
                Section::TABLES => ['users' => 'def'],
            ])],
            [],
        );

        $result = $this->target->compare($dump1, $dump2);

        self::assertEquals(
            [
                'schemas' => ['auth' => 'auth'],
                'tables' => ['auth.tokens' => 'token_def'],
            ],
            $result['expected'],
        );
        self::assertEmpty($result['actual']);
    }

    public function testNoDifferences(): void
    {
        $structure = [new SchemaDump('public', [
            Section::TABLES => ['users' => 'def'],
        ])];

        $dump1 = new Dump($structure, ['ext' => 'schema']);
        $dump2 = new Dump($structure, ['ext' => 'schema']);

        $result = $this->target->compare($dump1, $dump2);

        self::assertEmpty($result['expected']);
        self::assertEmpty($result['actual']);
    }
}
