<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Filesystem;

class SectionWriter
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
     * @param array $sectionContent
     * @throws \Exception
     */
    public function writeSection($sectionPath, array $sectionContent)
    {
        if (!$sectionContent) {
            return;
        }

        $this->helper->mkdir($sectionPath, 0775, true);

        $parts = pathinfo($sectionPath);
        $sectionName = $parts['filename'];

        foreach ($sectionContent as $name => $content) {
            if (in_array($sectionName, ['functions', 'triggers'])) {
                $name = $name . '.sql';
            } else {
                $name = $name . '.txt';
            }

            $this->helper->filePutContents($sectionPath . '/' . $name, $content);
        }
    }
}
