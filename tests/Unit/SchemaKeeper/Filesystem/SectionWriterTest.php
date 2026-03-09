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
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Filesystem\{FilesystemHelper, SectionWriter};
use SchemaKeeper\Tests\Unit\UnitTestCase;

class SectionWriterTest extends UnitTestCase
{
    private SectionWriter $target;

    /** @var FilesystemHelper&MockInterface */
    private FilesystemHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = Mockery::mock(FilesystemHelper::class);
        $this->helper->shouldReceive('mkdir')->andReturnTrue()->byDefault();
        $this->helper->shouldReceive('encodeName')->andReturnArg(0)->byDefault();

        $this->target = new SectionWriter($this->helper);
    }

    public function testOk(): void
    {
        $this->helper->shouldReceive('filePutContents')->with('/etc/tables/test1.txt', 'content1')->once()->ordered();
        $this->helper->shouldReceive('filePutContents')->with('/etc/tables/test2.txt', 'content2')->once()->ordered();

        $this->target->writeSection('/etc/tables', [
            'test1' => 'content1',
            'test2' => 'content2',
        ]);
    }

    public function testSqlExtension(): void
    {
        $this->helper->shouldReceive('filePutContents')
            ->with('/etc/functions/my_func.sql', 'CREATE FUNCTION ...')
            ->once()
        ;

        $this->target->writeSection('/etc/functions', [
            'my_func' => 'CREATE FUNCTION ...',
        ]);
    }

    public function testEmptyContent(): void
    {
        $this->helper->shouldNotReceive('mkdir');
        $this->target->writeSection('/etc/tables', []);
    }

    public function testEmptyNameThrowsException(): void
    {
        $this->helper->shouldReceive('encodeName')->with('')->andThrow(
            new KeeperException('Empty name for filesystem'),
        );

        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Empty name for filesystem');

        $this->target->writeSection('/etc/tables', [
            '' => 'content',
        ]);
    }
}
