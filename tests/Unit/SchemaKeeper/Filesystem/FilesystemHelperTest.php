<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Filesystem;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class FilesystemHelperTest extends UnitTestCase
{
    private FilesystemHelper $target;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new FilesystemHelper();
        $this->tempDir = sys_get_temp_dir() . '/schema_keeper_test_' . uniqid();
        mkdir($this->tempDir, 0775, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDir($this->tempDir);
        }

        parent::tearDown();
    }

    public function testIsDir(): void
    {
        $file = $this->tempDir . '/file.txt';
        file_put_contents($file, 'test');

        self::assertTrue($this->target->isDir($this->tempDir));
        self::assertFalse($this->target->isDir($file));
        self::assertFalse($this->target->isDir($this->tempDir . '/nonexistent'));
    }

    public function testIsFile(): void
    {
        $file = $this->tempDir . '/file.txt';
        file_put_contents($file, 'test');

        self::assertTrue($this->target->isFile($file));
        self::assertFalse($this->target->isFile($this->tempDir));
        self::assertFalse($this->target->isFile($this->tempDir . '/nonexistent'));
    }

    public function testIsLink(): void
    {
        $target = $this->tempDir . '/target.txt';
        $link = $this->tempDir . '/link.txt';
        file_put_contents($target, 'test');
        symlink($target, $link);

        self::assertTrue($this->target->isLink($link));
        self::assertFalse($this->target->isLink($target));
        self::assertFalse($this->target->isLink($this->tempDir . '/nonexistent'));
    }

    public function testIsDirEmptyWithEmptyDir(): void
    {
        self::assertTrue($this->target->isDirEmpty($this->tempDir));
    }

    public function testIsDirEmptyWithNonEmptyDir(): void
    {
        file_put_contents($this->tempDir . '/file.txt', 'test');
        self::assertFalse($this->target->isDirEmpty($this->tempDir));
    }

    public function testIsDirEmptyWithNonExistentPath(): void
    {
        self::assertTrue($this->target->isDirEmpty($this->tempDir . '/nonexistent'));
    }

    public function testIsDirEmptyWithSubdirectoryOnly(): void
    {
        mkdir($this->tempDir . '/subdir');
        self::assertFalse($this->target->isDirEmpty($this->tempDir));
    }

    public function testFileGetContents(): void
    {
        $file = $this->tempDir . '/file.txt';
        file_put_contents($file, 'hello world');
        self::assertSame('hello world', $this->target->fileGetContents($file));
    }

    public function testFileGetContentsEmptyFile(): void
    {
        $file = $this->tempDir . '/empty.txt';
        file_put_contents($file, '');
        self::assertSame('', $this->target->fileGetContents($file));
    }

    public function testFileGetContentsNonExistentThrows(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('file_get_contents error on:');
        $this->target->fileGetContents($this->tempDir . '/nonexistent.txt');
    }

    public function testFilePutContents(): void
    {
        $file = $this->tempDir . '/output.txt';
        $this->target->filePutContents($file, 'data');
        self::assertSame('data', file_get_contents($file));
    }

    public function testFilePutContentsOverwrite(): void
    {
        $file = $this->tempDir . '/output.txt';
        $this->target->filePutContents($file, 'first');
        $this->target->filePutContents($file, 'second');
        self::assertSame('second', file_get_contents($file));
    }

    public function testFilePutContentsInvalidPathThrows(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('file_put_contents error on:');
        $this->target->filePutContents($this->tempDir . '/nonexistent/file.txt', 'data');
    }

    public function testGlobFindsFiles(): void
    {
        file_put_contents($this->tempDir . '/a.txt', '');
        file_put_contents($this->tempDir . '/b.txt', '');
        file_put_contents($this->tempDir . '/c.sql', '');

        $result = $this->target->glob($this->tempDir . '/*.txt');
        sort($result);
        self::assertSame([
            $this->tempDir . '/a.txt',
            $this->tempDir . '/b.txt',
        ], $result);
    }

    public function testGlobNoMatches(): void
    {
        $result = $this->target->glob($this->tempDir . '/*.xyz');
        self::assertSame([], $result);
    }

    public function testMkdir(): void
    {
        $dir = $this->tempDir . '/newdir';
        $this->target->mkdir($dir);
        self::assertTrue(is_dir($dir));
    }

    public function testMkdirRecursive(): void
    {
        $dir = $this->tempDir . '/a/b/c';
        $this->target->mkdir($dir, 0775, true);
        self::assertTrue(is_dir($dir));
    }

    public function testMkdirExistingThrows(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('mkdir error on:');
        $this->target->mkdir($this->tempDir);
    }

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            unlink($path);

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isLink() || !$item->isDir()) {
                unlink($item->getPathname());
            } else {
                rmdir($item->getPathname());
            }
        }

        rmdir($path);
    }
}
