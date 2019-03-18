<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Filesystem;

use Mockery\MockInterface;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionReader;
use SchemaKeeper\Tests\SchemaTestCase;

class SectionReaderTest extends SchemaTestCase
{
    /**
     * @var SectionReader
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
        $this->helper->shouldReceive('isDir')->andReturnTrue()->byDefault();

        $this->target = new SectionReader($this->helper);
    }

    public function testOk()
    {
        $files = [
            '/etc/file1.sql',
            '/etc/file2.sh',
            '/etc/file3.txt',
        ];

        $this->helper->shouldReceive('glob')->with('/etc/*')->andReturn($files)->once();

        $this->helper->shouldReceive('fileGetContents')->with('/etc/file1.sql')->andReturn('file_content1')->ordered();
        $this->helper->shouldReceive('fileGetContents')->with('/etc/file3.txt')->andReturn('file_content2')->ordered();

        $actual = $this->target->readSection('/etc');

        $expected = [
            'file1' => 'file_content1',
            'file3' => 'file_content2'
        ];

        self::assertEquals($expected, $actual);
    }

    public function testNotExistedDirectory()
    {
        $this->helper->shouldReceive('isDir')->andReturnFalse()->once();
        $this->helper->shouldNotReceive('glob');

        $actual = $this->target->readSection('/etc');

        self::assertEquals([], $actual);
    }
}
