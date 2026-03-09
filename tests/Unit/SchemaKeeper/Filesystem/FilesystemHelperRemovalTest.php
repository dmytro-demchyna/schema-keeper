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

class FilesystemHelperRemovalTest extends UnitTestCase
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

    public function testRmDirIfExistedNonExistent(): void
    {
        $this->expectNotToPerformAssertions();

        $this->target->rmDirIfExisted($this->tempDir . '/nonexistent');
    }

    public function testRmDirIfExistedFile(): void
    {
        $file = $this->tempDir . '/file.txt';
        file_put_contents($file, 'test');
        $this->target->rmDirIfExisted($file);
        self::assertFalse(file_exists($file));
    }

    public function testRmDirIfExistedEmptyDir(): void
    {
        $dir = $this->tempDir . '/emptydir';
        mkdir($dir);
        $this->target->rmDirIfExisted($dir);
        self::assertFalse(file_exists($dir));
    }

    public function testRmDirIfExistedNestedDir(): void
    {
        $dir = $this->tempDir . '/nested';
        mkdir($dir . '/sub/deep', 0775, true);
        file_put_contents($dir . '/file.txt', 'a');
        file_put_contents($dir . '/sub/file.txt', 'b');
        file_put_contents($dir . '/sub/deep/file.txt', 'c');

        $this->target->rmDirIfExisted($dir);
        self::assertFalse(file_exists($dir));
    }

    public function testRmDirIfExistedSymlinkThrows(): void
    {
        $target = $this->tempDir . '/target';
        mkdir($target);
        $link = $this->tempDir . '/link';
        symlink($target, $link);

        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Refusing to remove symlink: ' . $link);
        $this->target->rmDirIfExisted($link);
    }

    public function testRmDirIfExistedDirContainingSymlink(): void
    {
        $dir = $this->tempDir . '/withlink';
        mkdir($dir);
        $target = $this->tempDir . '/target.txt';
        file_put_contents($target, 'test');
        symlink($target, $dir . '/link.txt');

        $this->target->rmDirIfExisted($dir);
        self::assertFalse(file_exists($dir));
        self::assertTrue(file_exists($target));
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
