<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Filesystem;

use Mockery\MockInterface;
use SchemaKeeper\Filesystem\DumpReader;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionReader;
use SchemaKeeper\Tests\SchemaTestCase;

class DumpReaderTest extends SchemaTestCase
{
    /**
     * @var DumpReader
     */
    private $target;

    /**
     * @var SectionReader|MockInterface
     */
    private $sectionReader;

    /**
     * @var FilesystemHelper|MockInterface
     */
    private $helper;

    public function setUp()
    {
        parent::setUp();

        $this->helper = \Mockery::mock(FilesystemHelper::class);
        $this->sectionReader = \Mockery::mock(SectionReader::class);

        $this->target = new DumpReader($this->sectionReader, $this->helper);
    }

    public function testOk()
    {
        $this->helper->shouldReceive('glob')->with('/etc/structure/*')->andReturn(['/etc/schema'])->once();

        $extensions = ['ext1', 'ext2'];
        $tables = ['table' => 'table_content'];
        $views = ['view' => 'view_content'];
        $materializedViews = ['m_view' => 'm_view_content'];
        $types = ['type' => 'type_content'];
        $functions = ['function' => 'function_content'];
        $triggers = ['trigger' => 'trigger_content'];
        $sequences = ['sequence' => 'sequence_content'];

        $this->sectionReader->shouldReceive('readSection')->with('/etc/extensions')->andReturn($extensions)->once();
        $this->sectionReader->shouldReceive('readSection')->with('/etc/schema/tables')->andReturn($tables)->once();
        $this->sectionReader->shouldReceive('readSection')->with('/etc/schema/views')->andReturn($views)->once();
        $this->sectionReader->shouldReceive('readSection')->with('/etc/schema/materialized_views')->andReturn($materializedViews)->once();
        $this->sectionReader->shouldReceive('readSection')->with('/etc/schema/types')->andReturn($types)->once();
        $this->sectionReader->shouldReceive('readSection')->with('/etc/schema/functions')->andReturn($functions)->once();
        $this->sectionReader->shouldReceive('readSection')->with('/etc/schema/triggers')->andReturn($triggers)->once();
        $this->sectionReader->shouldReceive('readSection')->with('/etc/schema/sequences')->andReturn($sequences)->once();

        $dump = $this->target->read('/etc');

        self::assertEquals($extensions, $dump->getExtensions());
        self::assertCount(1, $dump->getSchemas());

        $structure = $dump->getSchemas()[0];
        self::assertEquals($tables, $structure->getTables());
        self::assertEquals($views, $structure->getViews());
        self::assertEquals($materializedViews, $structure->getMaterializedViews());
        self::assertEquals($types, $structure->getTypes());
        self::assertEquals($functions, $structure->getFunctions());
        self::assertEquals($triggers, $structure->getTriggers());
        self::assertEquals($sequences, $structure->getSequences());
    }
}
