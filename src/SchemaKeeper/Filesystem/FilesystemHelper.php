<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Filesystem;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SchemaKeeper\Exception\KeeperException;

final class FilesystemHelper
{
    private const MAX_ENCODED_NAME_BYTES = 250;

    public function isDir(string $path): bool
    {
        return is_dir($path);
    }

    public function isFile(string $path): bool
    {
        return is_file($path);
    }

    public function isLink(string $path): bool
    {
        return is_link($path);
    }

    public function isDirEmpty(string $path): bool
    {
        if (!is_dir($path)) {
            return true;
        }

        $handle = @opendir($path);

        if ($handle === false) {
            throw new KeeperException('opendir error on: ' . $path);
        }

        while (($entry = readdir($handle)) !== false) {
            if ($entry !== '.' && $entry !== '..') {
                closedir($handle);

                return false;
            }
        }

        closedir($handle);

        return true;
    }

    public function fileGetContents(string $filename): string
    {
        $content = @file_get_contents($filename);

        if ($content === false) {
            throw new KeeperException('file_get_contents error on: ' . $filename);
        }

        return $content;
    }

    public function filePutContents(string $filename, string $data): void
    {
        $result = @file_put_contents($filename, $data);

        if ($result === false) {
            throw new KeeperException('file_put_contents error on: ' . $filename);
        }
    }

    /**
     * @return string[]
     */
    public function glob(string $pattern): array
    {
        $result = @glob($pattern);

        return $result === false ? [] : $result;
    }

    public function mkdir(string $pathname, int $mode = 0775, bool $recursive = false): void
    {
        $result = @mkdir($pathname, $mode, $recursive);

        if ($result === false) {
            throw new KeeperException('mkdir error on: ' . $pathname);
        }
    }

    public function encodeName(string $name): string
    {
        if ($name === '') {
            throw new KeeperException('Empty name for filesystem');
        }

        $name = str_replace('~', '~~', $name);
        $name = str_replace('/', '~S', $name);
        $name = str_replace('\\', '~B', $name);
        $name = str_replace("\0", '~0', $name);
        $name = str_replace(':', '~C', $name);
        $name = str_replace('*', '~A', $name);
        $name = str_replace('?', '~Q', $name);
        $name = str_replace('"', '~E', $name);
        $name = str_replace('<', '~L', $name);
        $name = str_replace('>', '~G', $name);
        $name = str_replace('|', '~P', $name);

        if ($name[0] === '.') {
            $name = '~D' . substr($name, 1);
        }

        if (preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(\.|$)/i', $name)) {
            $name = '~N' . $name;
        }

        if (strlen($name) > self::MAX_ENCODED_NAME_BYTES) {
            throw new KeeperException(
                'Encoded name exceeds ' . self::MAX_ENCODED_NAME_BYTES
                . ' bytes (' . strlen($name) . '): "' . substr($name, 0, 50) . '..."',
            );
        }

        return $name;
    }

    public function decodeName(string $name): string
    {
        $result = '';
        $len = strlen($name);
        $i = 0;

        while ($i < $len) {
            if ($name[$i] === '~' && $i + 1 < $len) {
                $next = $name[$i + 1];

                if ($next === '~') {
                    $result .= '~';
                } elseif ($next === 'S') {
                    $result .= '/';
                } elseif ($next === 'B') {
                    $result .= '\\';
                } elseif ($next === '0') {
                    $result .= "\0";
                } elseif ($next === 'C') {
                    $result .= ':';
                } elseif ($next === 'A') {
                    $result .= '*';
                } elseif ($next === 'Q') {
                    $result .= '?';
                } elseif ($next === 'E') {
                    $result .= '"';
                } elseif ($next === 'L') {
                    $result .= '<';
                } elseif ($next === 'G') {
                    $result .= '>';
                } elseif ($next === 'P') {
                    $result .= '|';
                } elseif ($next === 'N' && $i === 0) {
                } elseif ($next === 'D' && $i === 0) {
                    $result .= '.';
                } else {
                    throw new KeeperException(
                        'Invalid encoded name: unknown escape "~' . $next . '" in "' . $name . '"',
                    );
                }
                $i += 2;
            } elseif ($name[$i] === '~') {
                throw new KeeperException('Invalid encoded name: trailing "~" in "' . $name . '"');
            } else {
                $result .= $name[$i];
                $i++;
            }
        }

        return $result;
    }

    public function rmDirIfExisted(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if ($this->isLink($path)) {
            throw new KeeperException('Refusing to remove symlink: ' . $path);
        }

        if (!$this->isDir($path)) {
            if (!@unlink($path)) {
                throw new KeeperException('unlink error on: ' . $path);
            }

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();

            if ($item->isLink()) {
                if (!@unlink($itemPath)) {
                    throw new KeeperException('unlink error on: ' . $itemPath);
                }
            } elseif ($item->isDir()) {
                if (!@rmdir($itemPath)) {
                    throw new KeeperException('rmdir error on: ' . $itemPath);
                }
            } else {
                if (!@unlink($itemPath)) {
                    throw new KeeperException('unlink error on: ' . $itemPath);
                }
            }
        }

        if (!@rmdir($path)) {
            throw new KeeperException('rmdir error on: ' . $path);
        }
    }
}
