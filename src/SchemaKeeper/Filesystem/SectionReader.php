<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Filesystem;

/**
 * @internal
 */
class SectionReader
{
    /**
     * @var FilesystemHelper
     */
    private $helper;


    public function __construct(FilesystemHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param string $sectionPath
     * @return array<string, string>
     * @throws \Exception
     */
    public function readSection(string $sectionPath): array
    {
        $list = [];

        if (!$this->helper->isDir($sectionPath)) {
            return [];
        }

        foreach ($this->helper->glob($sectionPath . '/*') as $itemPath) {
            $parts = pathinfo($itemPath);

            $parts['extension'] = $parts['extension'] ?? '';

            if (!in_array($parts['extension'], ['txt', 'sql'])) {
                continue;
            }

            $content = $this->helper->fileGetContents($itemPath);
            $list[$parts['filename']] = $content;
        }

        return $list;
    }
}
