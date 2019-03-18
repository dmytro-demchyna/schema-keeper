<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Filesystem;

use Mockery\MockInterface;
use SchemaKeeper\Core\Dump;
use SchemaKeeper\Core\SchemaStructure;
use SchemaKeeper\Filesystem\DumpWriter;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionWriter;
use SchemaKeeper\Tests\SchemaTestCase;

class DumpWriterTest extends SchemaTestCase
{
    /**
     * @var DumpWriter
     */
    private $target;

    /**
     * @var SectionWriter|MockInterface
     */
    private $sectionWriter;

    /**
     * @var FilesystemHelper|MockInterface
     */
    private $helper;

    public function setUp()
    {
        parent::setUp();

        $this->helper = \Mockery::mock(FilesystemHelper::class);
        $this->sectionWriter = \Mockery::mock(SectionWriter::class);

        $this->target = new DumpWriter($this->sectionWriter, $this->helper);
    }

    public function testOk()
    {
        $extensions = ['ext1', 'ext2'];
        $tables = ['table' => 'table_content'];
        $views = ['view' => 'view_content'];
        $materializedViews = ['m_view' => 'm_view_content'];
        $types = ['type' => 'type_content'];
        $functions = ['function' => 'function_content'];
        $triggers = ['trigger' => 'trigger_content'];
        $sequences = ['sequence' => 'sequence_content'];

        $this->helper->shouldReceive('rmDirIfExisted')->with('/etc/extensions')->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/extensions', $extensions)->once();
        $this->helper->shouldReceive('rmDirIfExisted')->with('/etc/structure/schema')->once();
        $this->helper->shouldReceive('mkdir')->with('/etc/structure/schema', 0775, true)->once();
        $this->helper->shouldReceive('filePutContents')->with('/etc/structure/schema/.gitkeep', '')->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/tables', $tables)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/views', $views)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/materialized_views', $materializedViews)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/types', $types)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/functions', $functions)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/triggers', $triggers)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/sequences', $sequences)->once();

        $structure = new SchemaStructure('schema');
        $structure->setTables($tables);
        $structure->setViews($views);
        $structure->setMaterializedViews($materializedViews);
        $structure->setTypes($types);
        $structure->setFunctions($functions);
        $structure->setTriggers($triggers);
        $structure->setSequences($sequences);

        $dump = new Dump([$structure], $extensions);
        $this->target->write('/etc', $dump);
    }
}
