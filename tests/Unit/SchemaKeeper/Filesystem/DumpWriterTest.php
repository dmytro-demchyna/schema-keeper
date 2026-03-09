<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Filesystem;

use Mockery;
use Mockery\MockInterface;
use SchemaKeeper\Dto\{Dump, SchemaDump, Section};
use SchemaKeeper\Filesystem\{DumpWriter, FilesystemHelper, SectionWriter};
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class DumpWriterTest extends UnitTestCase
{
    private DumpWriter $target;

    /** @var SectionWriter&MockInterface */
    private SectionWriter $sectionWriter;

    /** @var FilesystemHelper&MockInterface */
    private FilesystemHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = Mockery::mock(FilesystemHelper::class);
        $this->sectionWriter = Mockery::mock(SectionWriter::class);

        $this->target = new DumpWriter($this->sectionWriter, $this->helper);
    }

    public function testOk(): void
    {
        $extensions = ['ext1', 'ext2'];
        $tables = ['table' => 'table_content'];
        $views = ['view' => 'view_content'];
        $materializedViews = ['m_view' => 'm_view_content'];
        $types = ['type' => 'type_content'];
        $functions = ['function' => 'function_content'];
        $triggers = ['trigger' => 'trigger_content'];
        $sequences = ['sequence' => 'sequence_content'];
        $procedures = ['procedure' => 'procedure_content'];

        $this->helper->shouldReceive('isDir')->with('/etc')->andReturn(false)->twice();
        $this->helper->shouldReceive('mkdir')->with('/etc', 0775, true)->once();
        $this->helper->shouldReceive('filePutContents')->with('/etc/.schema-keeper', '')->once();
        $this->helper->shouldReceive('rmDirIfExisted')->with('/etc/extensions')->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/extensions', $extensions)->once();
        $this->helper->shouldReceive('rmDirIfExisted')->with('/etc/structure')->once();
        $this->helper->shouldReceive('encodeName')->with('schema')->andReturn('schema')->once();
        $this->helper->shouldReceive('mkdir')->with('/etc/structure/schema', 0775, true)->once();
        $this->helper->shouldReceive('filePutContents')->with('/etc/structure/schema/.gitkeep', '')->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/tables', $tables)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/views', $views)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/materialized_views', $materializedViews)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/types', $types)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/functions', $functions)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/triggers', $triggers)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/sequences', $sequences)->once();
        $this->sectionWriter->shouldReceive('writeSection')->with('/etc/structure/schema/procedures', $procedures)->once();

        $structure = new SchemaDump('schema', [
            Section::TABLES => $tables,
            Section::VIEWS => $views,
            Section::MATERIALIZED_VIEWS => $materializedViews,
            Section::TYPES => $types,
            Section::FUNCTIONS => $functions,
            Section::TRIGGERS => $triggers,
            Section::SEQUENCES => $sequences,
            Section::PROCEDURES => $procedures,
        ]);

        $dump = new Dump([$structure], $extensions);
        $this->target->write('/etc', $dump);
    }

    public function testWriteToEmptyDirSucceeds(): void
    {
        $this->helper->shouldReceive('isDir')->with('/empty')->andReturn(true);
        $this->helper->shouldReceive('isDirEmpty')->with('/empty')->andReturn(true);
        $this->helper->shouldReceive('filePutContents')->with('/empty/.schema-keeper', '')->once();
        $this->helper->shouldReceive('rmDirIfExisted')->with('/empty/extensions')->once();
        $this->helper->shouldReceive('rmDirIfExisted')->with('/empty/structure')->once();
        $this->sectionWriter->shouldReceive('writeSection')->zeroOrMoreTimes();

        $dump = new Dump([], []);
        $this->target->write('/empty', $dump);
    }

    public function testWriteToDirWithMarkerSucceeds(): void
    {
        $this->helper->shouldReceive('isDir')->with('/existing')->andReturn(true);
        $this->helper->shouldReceive('isDirEmpty')->with('/existing')->andReturn(false);
        $this->helper->shouldReceive('isFile')->with('/existing/.schema-keeper')->andReturn(true);
        $this->helper->shouldReceive('rmDirIfExisted')->with('/existing/extensions')->once();
        $this->helper->shouldReceive('rmDirIfExisted')->with('/existing/structure')->once();
        $this->sectionWriter->shouldReceive('writeSection')->zeroOrMoreTimes();

        $dump = new Dump([], []);
        $this->target->write('/existing', $dump);
    }

    public function testWriteToNonEmptyDirWithoutMarkerThrowsException(): void
    {
        $this->helper->shouldReceive('isDir')->with('/danger')->andReturn(true);
        $this->helper->shouldReceive('isDirEmpty')->with('/danger')->andReturn(false);
        $this->helper->shouldReceive('isFile')->with('/danger/.schema-keeper')->andReturn(false);

        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Directory "/danger" is not empty and does not contain a .schema-keeper marker file. Aborting to prevent accidental data loss. If this is the correct directory, create an empty .schema-keeper file in it.');

        $dump = new Dump([], []);
        $this->target->write('/danger', $dump);
    }
}
