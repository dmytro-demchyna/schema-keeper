<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Core;

use Mockery\MockInterface;
use SchemaKeeper\Core\Dump;
use SchemaKeeper\Core\Dumper;
use SchemaKeeper\Core\SchemaFilter;
use SchemaKeeper\Provider\PostgreSQL\PSQLProvider;
use SchemaKeeper\Tests\SchemaTestCase;

class DumperTest extends SchemaTestCase
{
    /**
     * @var Dumper
     */
    private $target;

    /**
     * @var PSQLProvider|MockInterface
     */
    private $provider;

    /**
     * @var SchemaFilter|MockInterface
     */
    private $filter;

    public function setUp()
    {
        parent::setUp();

        $this->provider = \Mockery::mock(PSQLProvider::class);
        $this->filter = \Mockery::mock(SchemaFilter::class);

        $this->target = new Dumper($this->provider, $this->filter);
    }

    public function testOk()
    {
        $schemas = ['schema1', 'schema1'];
        $extensions = ['ext1', 'ext2'];
        $tables = ['schema1.table1' => 'table_content1'];
        $views = ['schema1.view1' => 'view_content1'];
        $matViews = ['schema1.mat_view1' => 'mat_view_content1'];
        $types = ['schema1.type1' => 'type_content1'];
        $functions = ['schema1.function1' => 'function_content1'];
        $triggers = ['schema1.trigger1' => 'trigger_content1'];
        $sequences = ['schema1.sequence1' => 'sequence_content1'];

        $this->provider->shouldReceive('getSchemas')->andReturn($schemas)->once();
        $this->provider->shouldReceive('getExtensions')->andReturn($extensions)->once();
        $this->provider->shouldReceive('getTables')->andReturn($tables)->once();
        $this->provider->shouldReceive('getViews')->andReturn($views)->once();
        $this->provider->shouldReceive('getMaterializedViews')->andReturn($matViews)->once();
        $this->provider->shouldReceive('getTypes')->andReturn($types)->once();
        $this->provider->shouldReceive('getFunctions')->andReturn($functions)->once();
        $this->provider->shouldReceive('getTriggers')->andReturn($triggers)->once();
        $this->provider->shouldReceive('getSequences')->andReturn($sequences)->once();

        $this->filter->shouldReceive('filter')->andReturnUsing(function ($schemaName, $items) {
            return $items;
        });

        $actualDump = $this->target->dump();

        self::assertInstanceOf(Dump::class, $actualDump);
        self::assertCount(2, $actualDump->getSchemas());
        self::assertEquals($extensions, $actualDump->getExtensions());

        $schemaStructure1 = $actualDump->getSchemas()[0];
        $schemaStructure2 = $actualDump->getSchemas()[1];
        self::assertEquals($schemaStructure1, $schemaStructure2);

        self::assertEquals($tables, $schemaStructure1->getTables());
        self::assertEquals($views, $schemaStructure1->getViews());
        self::assertEquals($matViews, $schemaStructure1->getMaterializedViews());
        self::assertEquals($types, $schemaStructure1->getTypes());
        self::assertEquals($functions, $schemaStructure1->getFunctions());
        self::assertEquals($triggers, $schemaStructure1->getTriggers());
        self::assertEquals($sequences, $schemaStructure1->getSequences());
    }
}
