<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Filesystem;

use SchemaKeeper\Core\Dump;
use SchemaKeeper\Core\SchemaStructure;
use SchemaKeeper\Exception\KeeperException;

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


    public function __construct(SectionReader $sectionReader, FilesystemHelper $helper)
    {
        $this->sectionReader = $sectionReader;
        $this->helper = $helper;
    }

    public function read(string $path): Dump
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

        if (!$schemas) {
            throw new KeeperException('Dump is empty '.$path);
        }

        $dump = new Dump($schemas, $extensions);

        return $dump;
    }
}
