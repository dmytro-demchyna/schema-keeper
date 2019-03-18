<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Filesystem;

use Mockery\MockInterface;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionWriter;
use SchemaKeeper\Tests\SchemaTestCase;

class SectionWriterTest extends SchemaTestCase
{
    /**
     * @var SectionWriter
     */
    private $target;

    /**
     * @var FilesystemHelper|MockInterface
     */
    private $helper;

    public function setUp()
    {
        parent::setUp();

        $this->helper = \Mockery::mock(FilesystemHelper::class);
        $this->helper->shouldReceive('mkdir')->andReturnTrue()->byDefault();

        $this->target = new SectionWriter($this->helper);
    }

    public function testTablesOk()
    {
        $this->helper->shouldReceive('filePutContents')->with('/etc/tables/test1.txt', 'content1')->ordered();
        $this->helper->shouldReceive('filePutContents')->with('/etc/tables/test2.txt', 'content2')->ordered();

        $this->target->writeSection('/etc/tables', [
            'test1' => 'content1',
            'test2' => 'content2',
        ]);
    }

    public function testFunctionsOk()
    {
        $this->helper->shouldReceive('filePutContents')->with('/etc/functions/test1.sql', 'content1')->ordered();

        $this->target->writeSection('/etc/functions', [
            'test1' => 'content1',
        ]);
    }

    public function testTriggersOk()
    {
        $this->helper->shouldReceive('filePutContents')->with('/etc/triggers/test1.sql', 'content1')->ordered();

        $this->target->writeSection('/etc/triggers', [
            'test1' => 'content1',
        ]);
    }

    public function testEmptyContent()
    {
        $this->helper->shouldNotReceive('mkdir');
        $this->target->writeSection('/etc/tables', []);
    }
}
