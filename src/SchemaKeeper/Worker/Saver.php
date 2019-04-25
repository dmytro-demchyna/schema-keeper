<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Worker;

use Exception;
use SchemaKeeper\Core\Dumper;
use SchemaKeeper\Core\SchemaFilter;
use SchemaKeeper\Filesystem\DumpWriter;
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\Filesystem\SectionWriter;
use SchemaKeeper\Provider\IProvider;

class Saver
{
    /**
     * @var Dumper
     */
    private $dumper;

    /**
     * @var DumpWriter
     */
    private $writer;

    /**
     * @param IProvider $provider
     */
    public function __construct(IProvider $provider)
    {
        $schemaFilter = new SchemaFilter();
        $this->dumper = new Dumper($provider, $schemaFilter);
        $helper = new FilesystemHelper();
        $sectionWriter = new SectionWriter($helper);
        $this->writer = new DumpWriter($sectionWriter, $helper);
    }

    /**
     * @param string $destinationPath
     * @throws Exception
     */
    public function execute($destinationPath)
    {
        $dump = $this->dumper->dump();
        $this->writer->write($destinationPath, $dump);
    }
}
