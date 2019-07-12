<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Filesystem;

use Exception;
use SchemaKeeper\Exception\KeeperException;

class FilesystemHelper
{
    public function isDir(string $path): bool
    {
        return is_dir($path);
    }

    public function fileGetContents(string $filename): string
    {
        $content = file_get_contents($filename);

        if ($content === false) {
            throw new KeeperException('file_get_contents error on: '.$filename);
        }

        return $content;
    }

    public function filePutContents(string $filename, string $data): void
    {
        $result = file_put_contents($filename, $data);

        if ($result === false) {
            throw new KeeperException('file_put_contents error on: '.$filename);
        }
    }

    public function glob(string $pattern): array
    {
        $result = glob($pattern);

        if ($result === false) {
            throw new KeeperException('glob() error');
        }

        return $result;
    }

    public function mkdir(string $pathname, int $mode = 0775, bool $recursive = false): void
    {
        $result = mkdir($pathname, $mode, $recursive);

        if ($result === false) {
            throw new KeeperException('mkdir error on: '.$pathname);
        }
    }

    public function rmDirIfExisted(string $path): void
    {
        if ($this->isDir($path)) {
            shell_exec("rm -rf ".escapeshellarg($path));
        }
    }
}
