<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Collector;

use Mockery;
use Mockery\MockInterface;
use SchemaKeeper\Dto\{Dump, Section};
use SchemaKeeper\Collector\{Dumper, SchemaItemFilter};
use SchemaKeeper\Provider\IProvider;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class DumperTest extends UnitTestCase
{
    private Dumper $target;

    /** @var IProvider&MockInterface */
    private IProvider $provider;

    /** @var SchemaItemFilter&MockInterface */
    private SchemaItemFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = Mockery::mock(IProvider::class);
        $this->filter = Mockery::mock(SchemaItemFilter::class);

        $this->target = new Dumper($this->provider, $this->filter);
    }

    public function testOk(): void
    {
        $schemas = ['schema1', 'schema2'];
        $extensions = ['ext1', 'ext2'];
        $tables = ['schema1.table1' => 'table_content1'];
        $views = ['schema1.view1' => 'view_content1'];
        $matViews = ['schema1.mat_view1' => 'mat_view_content1'];
        $types = ['schema1.type1' => 'type_content1'];
        $functions = ['schema1.function1' => 'function_content1'];
        $triggers = ['schema1.trigger1' => 'trigger_content1'];
        $sequences = ['schema1.sequence1' => 'sequence_content1'];
        $procedures = ['schema1.procedure1' => 'procedure_content1'];

        $sectionData = [
            Section::TABLES => $tables,
            Section::VIEWS => $views,
            Section::MATERIALIZED_VIEWS => $matViews,
            Section::TYPES => $types,
            Section::FUNCTIONS => $functions,
            Section::TRIGGERS => $triggers,
            Section::SEQUENCES => $sequences,
            Section::PROCEDURES => $procedures,
        ];

        $this->provider->shouldReceive('getSchemas')->andReturn($schemas)->once();
        $this->provider->shouldReceive('getExtensions')->andReturn($extensions)->once();
        $this->provider->shouldReceive('getData')->andReturnUsing(static fn (string $section) => $sectionData[$section] ?? []);

        $this->filter->shouldReceive('filter')->andReturnUsing(static fn ($schemaName, $items) => $items);

        $actualDump = $this->target->dump();

        self::assertInstanceOf(Dump::class, $actualDump);
        self::assertCount(2, $actualDump->getSchemas());
        self::assertEquals($extensions, $actualDump->getExtensions());

        $schemaStructure1 = $actualDump->getSchemas()[0];
        self::assertEquals('schema1', $schemaStructure1->getSchemaName());
        self::assertEquals($tables, $schemaStructure1->getSection(Section::TABLES));
        self::assertEquals($views, $schemaStructure1->getSection(Section::VIEWS));
        self::assertEquals($matViews, $schemaStructure1->getSection(Section::MATERIALIZED_VIEWS));
        self::assertEquals($types, $schemaStructure1->getSection(Section::TYPES));
        self::assertEquals($functions, $schemaStructure1->getSection(Section::FUNCTIONS));
        self::assertEquals($triggers, $schemaStructure1->getSection(Section::TRIGGERS));
        self::assertEquals($sequences, $schemaStructure1->getSection(Section::SEQUENCES));

        $schemaStructure2 = $actualDump->getSchemas()[1];
        self::assertEquals('schema2', $schemaStructure2->getSchemaName());
    }

    public function testSkipSections(): void
    {
        $skippedSections = [Section::TABLES, Section::TRIGGERS];

        $provider = Mockery::mock(IProvider::class);
        $provider->shouldReceive('getSchemas')->andReturn(['public']);
        $provider->shouldReceive('getExtensions')->andReturn([]);
        $provider->shouldReceive('getData')->andReturnUsing(static function (string $section) {
            if ($section === Section::FUNCTIONS) {
                return ['public.my_func()' => 'func_def'];
            }

            return [];
        });

        $dumper = new Dumper($provider, new SchemaItemFilter(), $skippedSections);
        $dump = $dumper->dump();

        $provider->shouldNotHaveReceived('getData', [Section::TABLES]);
        $provider->shouldNotHaveReceived('getData', [Section::TRIGGERS]);

        $schema = $dump->getSchemas()[0];
        self::assertEquals([], $schema->getSection(Section::TABLES));
        self::assertEquals([], $schema->getSection(Section::TRIGGERS));
        self::assertEquals(['my_func()' => 'func_def'], $schema->getSection(Section::FUNCTIONS));
    }

    public function testFiltersBySchema(): void
    {
        $provider = Mockery::mock(IProvider::class);
        $provider->shouldReceive('getSchemas')->andReturn(['public', 'auth']);
        $provider->shouldReceive('getExtensions')->andReturn([]);
        $provider->shouldReceive('getData')->andReturnUsing(static function (string $section) {
            if ($section === Section::TABLES) {
                return [
                    'public.users' => 'users_def',
                    'public.posts' => 'posts_def',
                    'auth.roles' => 'roles_def',
                ];
            }

            if ($section === Section::FUNCTIONS) {
                return [
                    'public.get_user()' => 'func_def',
                    'auth.check_role()' => 'auth_func_def',
                ];
            }

            return [];
        });

        $dumper = new Dumper($provider, new SchemaItemFilter());
        $dump = $dumper->dump();

        $schemas = $dump->getSchemas();
        self::assertCount(2, $schemas);

        $public = $schemas[0];
        self::assertEquals('public', $public->getSchemaName());
        self::assertEquals(['users' => 'users_def', 'posts' => 'posts_def'], $public->getSection(Section::TABLES));
        self::assertEquals(['get_user()' => 'func_def'], $public->getSection(Section::FUNCTIONS));
        self::assertEmpty($public->getSection(Section::VIEWS));

        $auth = $schemas[1];
        self::assertEquals('auth', $auth->getSchemaName());
        self::assertEquals(['roles' => 'roles_def'], $auth->getSection(Section::TABLES));
        self::assertEquals(['check_role()' => 'auth_func_def'], $auth->getSection(Section::FUNCTIONS));
        self::assertEmpty($auth->getSection(Section::VIEWS));
    }
}
