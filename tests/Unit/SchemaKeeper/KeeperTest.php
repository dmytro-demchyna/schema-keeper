<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper;

use Mockery;
use Mockery\MockInterface;
use SchemaKeeper\Comparator\DumpComparator;
use SchemaKeeper\Dto\{Dump, Section};
use SchemaKeeper\Exception\{KeeperException, NotEquals};
use SchemaKeeper\Collector\{Dumper, SchemaItemFilter};
use SchemaKeeper\Filesystem\{DumpReader, DumpWriter, FilesystemHelper, SectionReader, SectionWriter};
use SchemaKeeper\Keeper;
use SchemaKeeper\Provider\IProvider;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class KeeperTest extends UnitTestCase
{
    public function testSaveDump(): void
    {
        $sectionData = [
            Section::TABLES => ['public.users' => 'table_content'],
            Section::VIEWS => ['public.active_users' => 'view_content'],
            Section::MATERIALIZED_VIEWS => ['public.user_stats' => 'mat_view_content'],
            Section::FUNCTIONS => [
                'public.get_user(integer)' => 'function_content',
                'test_schema.helper(text)' => 'schema_function_content',
            ],
            Section::PROCEDURES => ['public.sync()' => 'procedure_content'],
            Section::TRIGGERS => ['public.users.on_update' => 'trigger_content'],
            Section::TYPES => ['public.status' => 'type_content'],
            Section::SEQUENCES => ['public.users_id_seq' => 'sequence_content'],
        ];

        $provider = Mockery::mock(IProvider::class);
        $provider->shouldReceive('getSchemas')->andReturn(['public' => 'public', 'test_schema' => 'test_schema']);
        $provider->shouldReceive('getExtensions')->andReturn(['plpgsql' => 'pg_catalog']);
        $provider->shouldReceive('getData')->andReturnUsing(static fn (string $section) => $sectionData[$section] ?? []);

        [$keeper, $helper] = $this->createKeeper($provider);
        $tmpDir = sys_get_temp_dir() . '/schema_keeper_saver_' . uniqid();

        try {
            $keeper->saveDump($tmpDir);

            self::assertEquals([
                $tmpDir . '/extensions',
                $tmpDir . '/structure',
            ], glob($tmpDir . '/*'));

            self::assertEquals([
                $tmpDir . '/structure/public',
                $tmpDir . '/structure/test_schema',
            ], glob($tmpDir . '/structure/*'));

            self::assertEquals([
                $tmpDir . '/structure/public/functions',
                $tmpDir . '/structure/public/materialized_views',
                $tmpDir . '/structure/public/procedures',
                $tmpDir . '/structure/public/sequences',
                $tmpDir . '/structure/public/tables',
                $tmpDir . '/structure/public/triggers',
                $tmpDir . '/structure/public/types',
                $tmpDir . '/structure/public/views',
            ], glob($tmpDir . '/structure/public/*'));

            self::assertEquals('pg_catalog', file_get_contents($tmpDir . '/extensions/plpgsql.txt'));
            self::assertEquals('table_content', file_get_contents($tmpDir . '/structure/public/tables/users.txt'));
            self::assertEquals('view_content', file_get_contents($tmpDir . '/structure/public/views/active_users.txt'));
            self::assertEquals('mat_view_content', file_get_contents($tmpDir . '/structure/public/materialized_views/user_stats.txt'));
            self::assertEquals('function_content', file_get_contents($tmpDir . '/structure/public/functions/get_user(integer).sql'));
            self::assertEquals('schema_function_content', file_get_contents($tmpDir . '/structure/test_schema/functions/helper(text).sql'));
            self::assertEquals('procedure_content', file_get_contents($tmpDir . '/structure/public/procedures/sync().sql'));
            self::assertEquals('trigger_content', file_get_contents($tmpDir . '/structure/public/triggers/users.on_update.sql'));
            self::assertEquals('type_content', file_get_contents($tmpDir . '/structure/public/types/status.txt'));
            self::assertEquals('sequence_content', file_get_contents($tmpDir . '/structure/public/sequences/users_id_seq.txt'));
        } finally {
            $helper->rmDirIfExisted($tmpDir);
        }
    }

    public function testSaveDumpFunctionFileNameEdgeCases(): void
    {
        $names = [
            'func_brackets(integer[])' => 'content',
            'func_spaces(double precision, timestamp without time zone)' => 'content',
            'func_dot_type(extensions.gbtreekey_var, internal)' => 'content',
            'func_multi(integer, integer, text)' => 'content',
            'func_empty()' => 'content',
            'has/slash()' => 'content',
            '.hidden(integer)' => 'content',
            'my~func(integer)' => 'content',
            'back\slash()' => 'content',
            'tricky~/func(integer[], text)' => 'content',
        ];

        $encodedNames = [
            'func_brackets(integer[]).sql' => 'content',
            'func_spaces(double precision, timestamp without time zone).sql' => 'content',
            'func_dot_type(extensions.gbtreekey_var, internal).sql' => 'content',
            'func_multi(integer, integer, text).sql' => 'content',
            'func_empty().sql' => 'content',
            'has~Sslash().sql' => 'content',
            '~Dhidden(integer).sql' => 'content',
            'my~~func(integer).sql' => 'content',
            'back~Bslash().sql' => 'content',
            'tricky~~~Sfunc(integer[], text).sql' => 'content',
        ];

        $functions = [];

        foreach ($names as $name => $content) {
            $functions['public.' . $name] = $content;
        }

        $provider = Mockery::mock(IProvider::class);
        $provider->shouldReceive('getSchemas')->andReturn(['public' => 'public']);
        $provider->shouldReceive('getExtensions')->andReturn([]);
        $provider->shouldReceive('getData')->andReturnUsing(static fn (string $section) => $section === Section::FUNCTIONS ? $functions : []);

        [$keeper, $helper] = $this->createKeeper($provider);
        $tmpDir = sys_get_temp_dir() . '/schema_keeper_edge_' . uniqid();

        try {
            $keeper->saveDump($tmpDir);

            $functionsDir = $tmpDir . '/structure/public/functions';

            $actualFiles = [];

            foreach (glob($functionsDir . '/*') as $file) {
                $actualFiles[basename($file)] = file_get_contents($file);
            }

            ksort($actualFiles);
            self::assertEquals($encodedNames, $actualFiles);

            $reader = new SectionReader($helper);
            $result = $reader->readSection($functionsDir);

            ksort($names);
            ksort($result);
            self::assertEquals($names, $result);
        } finally {
            $helper->rmDirIfExisted($tmpDir);
        }
    }

    public function testVerifyDumpOk(): void
    {
        $actualDump = new Dump([], []);
        $expectedDump = new Dump([], []);

        /** @var Dumper&MockInterface $dumper */
        $dumper = Mockery::mock(Dumper::class);
        /** @var DumpReader&MockInterface $dumpReader */
        $dumpReader = Mockery::mock(DumpReader::class);
        /** @var DumpComparator&MockInterface $comparator */
        $comparator = Mockery::mock(DumpComparator::class);
        $dumpWriter = Mockery::mock(DumpWriter::class);

        $dumper->shouldReceive('dump')->once()->andReturn($actualDump);
        $dumpReader->shouldReceive('read')->with('/path')->once()->andReturn($expectedDump);
        $comparator->shouldReceive('compare')->with($expectedDump, $actualDump)->once()->andReturn([
            'expected' => [],
            'actual' => [],
        ]);

        $keeper = new Keeper($dumper, $dumpWriter, $dumpReader, $comparator);
        $keeper->verifyDump('/path');
    }

    public function testVerifyDumpThrowsNotEquals(): void
    {
        $actualDump = new Dump([], []);
        $expectedDump = new Dump([], []);

        /** @var Dumper&MockInterface $dumper */
        $dumper = Mockery::mock(Dumper::class);
        /** @var DumpReader&MockInterface $dumpReader */
        $dumpReader = Mockery::mock(DumpReader::class);
        /** @var DumpComparator&MockInterface $comparator */
        $comparator = Mockery::mock(DumpComparator::class);
        $dumpWriter = Mockery::mock(DumpWriter::class);

        $dumper->shouldReceive('dump')->once()->andReturn($actualDump);
        $dumpReader->shouldReceive('read')->with('/path')->once()->andReturn($expectedDump);
        $comparator->shouldReceive('compare')->with($expectedDump, $actualDump)->once()->andReturn([
            'expected' => ['tables' => ['t' => 'old']],
            'actual' => ['tables' => ['t' => 'new']],
        ]);

        $this->expectException(NotEquals::class);
        $this->expectExceptionMessage('Dump and current database are not equal');

        $keeper = new Keeper($dumper, $dumpWriter, $dumpReader, $comparator);
        $keeper->verifyDump('/path');
    }

    public function testSaveDumpThrowsOnEmptyDump(): void
    {
        $provider = Mockery::mock(IProvider::class);
        $provider->shouldReceive('getSchemas')->andReturn([]);
        $provider->shouldReceive('getExtensions')->andReturn([]);
        $provider->shouldReceive('getData')->andReturn([]);

        [$keeper] = $this->createKeeper($provider);

        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Dump is empty: no schemas to save');

        $keeper->saveDump('/unused');
    }

    private function createKeeper(IProvider $provider): array
    {
        $helper = new FilesystemHelper();
        $filter = new SchemaItemFilter();
        $dumper = new Dumper($provider, $filter);
        $sectionWriter = new SectionWriter($helper);
        $dumpWriter = new DumpWriter($sectionWriter, $helper);
        $sectionReader = new SectionReader($helper);
        $dumpReader = new DumpReader($sectionReader, $helper);
        $comparator = Mockery::mock(DumpComparator::class);

        $keeper = new Keeper($dumper, $dumpWriter, $dumpReader, $comparator);

        return [$keeper, $helper];
    }
}
