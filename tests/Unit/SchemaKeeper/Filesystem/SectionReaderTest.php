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
use SchemaKeeper\Filesystem\{FilesystemHelper, SectionReader};
use SchemaKeeper\Tests\Unit\UnitTestCase;

class SectionReaderTest extends UnitTestCase
{
    private SectionReader $target;

    /** @var FilesystemHelper&MockInterface */
    private FilesystemHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = Mockery::mock(FilesystemHelper::class);
        $this->helper->shouldReceive('isDir')->andReturnTrue()->byDefault();
        $this->helper->shouldReceive('decodeName')->andReturnArg(0)->byDefault();

        $this->target = new SectionReader($this->helper);
    }

    public function testOk(): void
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
            'file3' => 'file_content2',
        ];

        self::assertEquals($expected, $actual);
    }

    public function testNotExistedDirectory(): void
    {
        $this->helper->shouldReceive('isDir')->andReturnFalse()->once();
        $this->helper->shouldNotReceive('glob');

        $actual = $this->target->readSection('/etc');

        self::assertEquals([], $actual);
    }
}
