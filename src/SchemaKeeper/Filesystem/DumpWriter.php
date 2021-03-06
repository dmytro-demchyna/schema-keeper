<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Filesystem;

use Exception;
use SchemaKeeper\Core\Dump;

/**
 * @internal
 */
class DumpWriter
{
    /**
     * @var SectionWriter
     */
    private $sectionWriter;

    /**
     * @var FilesystemHelper
     */
    private $helper;


    public function __construct(SectionWriter $sectionWriter, FilesystemHelper $helper)
    {
        $this->sectionWriter = $sectionWriter;
        $this->helper = $helper;
    }

    public function write(string $path, Dump $dump): void
    {
        $structurePath = $path.'/structure';
        $extensionsPath = $path.'/extensions';

        $this->helper->rmDirIfExisted($extensionsPath);
        $this->sectionWriter->writeSection($extensionsPath, $dump->getExtensions());

        foreach ($dump->getSchemas() as $schemaDump) {
            $schemaPath = $structurePath.'/'.$schemaDump->getSchemaName();

            $this->helper->rmDirIfExisted($schemaPath);
            $this->helper->mkdir($schemaPath, 0775, true);

            $this->helper->filePutContents($schemaPath.'/.gitkeep', '');

            $this->sectionWriter->writeSection($schemaPath . '/tables', $schemaDump->getTables());
            $this->sectionWriter->writeSection($schemaPath . '/views', $schemaDump->getViews());
            $this->sectionWriter->writeSection($schemaPath . '/materialized_views', $schemaDump->getMaterializedViews());
            $this->sectionWriter->writeSection($schemaPath . '/types', $schemaDump->getTypes());
            $this->sectionWriter->writeSection($schemaPath . '/functions', $schemaDump->getFunctions());
            $this->sectionWriter->writeSection($schemaPath . '/triggers', $schemaDump->getTriggers());
            $this->sectionWriter->writeSection($schemaPath . '/sequences', $schemaDump->getSequences());
        }
    }
}
