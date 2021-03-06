<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Core;

use SchemaKeeper\Provider\IProvider;

/**
 * @internal
 */
class Dumper
{
    /**
     * @var IProvider
     */
    private $provider;

    /**
     * @var SchemaFilter
     */
    private $filter;


    public function __construct(IProvider $provider, SchemaFilter $filter)
    {
        $this->provider = $provider;
        $this->filter = $filter;
    }

    public function dump(): Dump
    {
        $schemas = [];

        $tables = $this->provider->getTables();
        $views = $this->provider->getViews();
        $materializedViews = $this->provider->getMaterializedViews();
        $types = $this->provider->getTypes();
        $functions = $this->provider->getFunctions();
        $triggers = $this->provider->getTriggers();
        $sequences = $this->provider->getSequences();

        foreach ($this->provider->getSchemas() as $schemaName) {
            $structure = new SchemaStructure($schemaName);

            $structure->setTables($this->filter->filter($schemaName, $tables));
            $structure->setViews($this->filter->filter($schemaName, $views));
            $structure->setMaterializedViews($this->filter->filter($schemaName, $materializedViews));
            $structure->setTypes($this->filter->filter($schemaName, $types));
            $structure->setFunctions($this->filter->filter($schemaName, $functions));
            $structure->setTriggers($this->filter->filter($schemaName, $triggers));
            $structure->setSequences($this->filter->filter($schemaName, $sequences));

            $schemas[] = $structure;
        }

        $dump = new Dump($schemas, $this->provider->getExtensions());

        return $dump;
    }
}
