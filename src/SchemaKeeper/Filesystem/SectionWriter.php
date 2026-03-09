<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Filesystem;

use SchemaKeeper\Dto\Section;

final class SectionWriter
{
    private FilesystemHelper $helper;

    public function __construct(FilesystemHelper $helper)
    {
        $this->helper = $helper;
    }

    public function writeSection(string $sectionPath, array $sectionContent): void
    {
        if (!$sectionContent) {
            return;
        }

        $this->helper->mkdir($sectionPath, 0775, true);

        $parts = pathinfo($sectionPath);
        $sectionName = $parts['filename'];

        $sqlSections = [
            Section::FUNCTIONS,
            Section::TRIGGERS,
            Section::PROCEDURES,
        ];

        foreach ($sectionContent as $name => $content) {
            $encodedName = $this->helper->encodeName((string) $name);

            if (in_array($sectionName, $sqlSections, true)) {
                $encodedName .= '.sql';
            } else {
                $encodedName .= '.txt';
            }

            $this->helper->filePutContents($sectionPath . '/' . $encodedName, $content);
        }
    }
}
