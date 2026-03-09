<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Filesystem;

use SchemaKeeper\Dto\{Dump, Section};
use SchemaKeeper\Exception\KeeperException;

final class DumpWriter
{
    private const MARKER_FILE = '.schema-keeper';

    private SectionWriter $sectionWriter;

    private FilesystemHelper $helper;

    public function __construct(SectionWriter $sectionWriter, FilesystemHelper $helper)
    {
        $this->sectionWriter = $sectionWriter;
        $this->helper = $helper;
    }

    public function write(string $path, Dump $dump): void
    {
        $markerPath = $path . '/' . self::MARKER_FILE;

        if ($this->helper->isDir($path) && !$this->helper->isDirEmpty($path)) {
            if (!$this->helper->isFile($markerPath)) {
                throw new KeeperException(
                    'Directory "' . $path . '" is not empty and does not contain a '
                    . self::MARKER_FILE . ' marker file. '
                    . 'Aborting to prevent accidental data loss. '
                    . 'If this is the correct directory, create an empty ' . self::MARKER_FILE . ' file in it.',
                );
            }
        } else {
            if (!$this->helper->isDir($path)) {
                $this->helper->mkdir($path, 0775, true);
            }
            $this->helper->filePutContents($markerPath, '');
        }

        $structurePath = $path . '/structure';
        $extensionsPath = $path . '/extensions';

        $this->helper->rmDirIfExisted($extensionsPath);
        $this->sectionWriter->writeSection($extensionsPath, $dump->getExtensions());

        $this->helper->rmDirIfExisted($structurePath);

        foreach ($dump->getSchemas() as $schemaDump) {
            $encodedSchema = $this->helper->encodeName($schemaDump->getSchemaName());
            $schemaPath = $structurePath . '/' . $encodedSchema;

            $this->helper->mkdir($schemaPath, 0775, true);

            $this->helper->filePutContents($schemaPath . '/.gitkeep', '');

            foreach (Section::all() as $section) {
                $this->sectionWriter->writeSection($schemaPath . '/' . $section, $schemaDump->getSection($section));
            }
        }
    }
}
