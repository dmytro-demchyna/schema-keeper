<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Filesystem;

final class SectionReader
{
    private FilesystemHelper $helper;

    public function __construct(FilesystemHelper $helper)
    {
        $this->helper = $helper;
    }

    public function readSection(string $sectionPath): array
    {
        $list = [];

        if (!$this->helper->isDir($sectionPath)) {
            return [];
        }

        foreach ($this->helper->glob($sectionPath . '/*') as $itemPath) {
            $parts = pathinfo($itemPath);

            if (!isset($parts['extension']) || !in_array($parts['extension'], ['txt', 'sql'], true)) {
                continue;
            }

            $content = $this->helper->fileGetContents($itemPath);
            $decodedName = $this->helper->decodeName($parts['filename']);
            $list[$decodedName] = $content;
        }

        return $list;
    }
}
