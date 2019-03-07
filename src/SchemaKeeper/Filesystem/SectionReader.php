<?php

namespace SchemaKeeper\Filesystem;

class SectionReader
{
    /**
     * @var FilesystemHelper
     */
    private $helper;

    /**
     * @param FilesystemHelper $helper
     */
    public function __construct(FilesystemHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param string $sectionPath
     * @return array
     * @throws \Exception
     */
    public function readSection($sectionPath)
    {
        $list = [];

        if (!$this->helper->isDir($sectionPath)) {
            return [];
        }

        foreach ($this->helper->glob($sectionPath . '/*') as $itemPath) {
            $parts = pathinfo($itemPath);

            if (!in_array($parts['extension'], ['txt', 'sql'])) {
                continue;
            }

            $content = $this->helper->fileGetContents($itemPath);
            $list[$parts['filename']] = $content;
        }

        return $list;
    }
}
