<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Filesystem;

use SchemaKeeper\Dto\{Dump, SchemaDump, Section};
use SchemaKeeper\Exception\KeeperException;

final class DumpReader
{
    private SectionReader $sectionReader;

    private FilesystemHelper $helper;

    /**
     * @var string[]
     */
    private array $skippedSections;

    public function __construct(SectionReader $sectionReader, FilesystemHelper $helper, array $skippedSections = [])
    {
        $this->sectionReader = $sectionReader;
        $this->helper = $helper;
        $this->skippedSections = $skippedSections;
    }

    public function read(string $path): Dump
    {
        $structurePath = $path . '/structure';
        $extensionsPath = $path . '/extensions';

        $extensions = $this->sectionReader->readSection($extensionsPath);

        $schemas = [];

        foreach ($this->helper->glob($structurePath . '/*') as $schemaPath) {
            if (!$this->helper->isDir($schemaPath)) {
                continue;
            }
            $schemaName = $this->helper->decodeName(basename($schemaPath));

            $sections = [];

            foreach (Section::all() as $section) {
                if (in_array($section, $this->skippedSections, true)) {
                    continue;
                }

                $sections[$section] = $this->sectionReader->readSection($schemaPath . '/' . $section);
            }

            $schemas[] = new SchemaDump($schemaName, $sections);
        }

        if (!$schemas) {
            throw new KeeperException('Dump is empty: ' . $path);
        }

        return new Dump($schemas, $extensions);
    }
}
