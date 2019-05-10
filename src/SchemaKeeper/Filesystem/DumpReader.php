<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Filesystem;

use SchemaKeeper\Core\Dump;
use SchemaKeeper\Core\SchemaStructure;

class DumpReader
{
    /**
     * @var SectionReader
     */
    private $sectionReader;

    /**
     * @var FilesystemHelper
     */
    private $helper;

    /**
     * @param SectionReader $sectionReader
     * @param FilesystemHelper $helper
     */
    public function __construct(SectionReader $sectionReader, FilesystemHelper $helper)
    {
        $this->sectionReader = $sectionReader;
        $this->helper = $helper;
    }

    /**
     * @param string $path
     * @return Dump
     * @throws \Exception
     */
    public function read($path)
    {
        $structurePath = $path.'/structure';
        $extensionsPath = $path.'/extensions';

        $extensions = $this->sectionReader->readSection($extensionsPath);

        $schemas = [];

        foreach ($this->helper->glob($structurePath . '/*') as $schemaPath) {
            $parts = pathinfo($schemaPath);
            $schemaName = $parts['filename'];

            $structure = new SchemaStructure($schemaName);

            $structure->setTables($this->sectionReader->readSection($schemaPath.'/tables'));
            $structure->setViews($this->sectionReader->readSection($schemaPath.'/views'));
            $structure->setMaterializedViews($this->sectionReader->readSection($schemaPath.'/materialized_views'));
            $structure->setTypes($this->sectionReader->readSection($schemaPath.'/types'));
            $structure->setFunctions($this->sectionReader->readSection($schemaPath.'/functions'));
            $structure->setTriggers($this->sectionReader->readSection($schemaPath.'/triggers'));
            $structure->setSequences($this->sectionReader->readSection($schemaPath.'/sequences'));

            $schemas[] = $structure;
        }

        $dump = new Dump($schemas, $extensions);

        return $dump;
    }
}
