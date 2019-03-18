<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Core;

class SchemaStructure
{
    /**
     * @var string
     */
    private $schemaName;

    /**
     * @var array
     */
    private $tables = [];

    /**
     * @var array
     */
    private $views = [];

    /**
     * @var array
     */
    private $materializedViews;

    /**
     * @var array
     */
    private $types = [];

    /**
     * @var array
     */
    private $functions = [];

    /**
     * @var array
     */
    private $triggers = [];

    /**
     * @var array
     */
    private $sequences = [];


    /**
     * @param string $schemaName
     */
    public function __construct($schemaName)
    {
        $this->schemaName = $schemaName;
    }

    /**
     * @return string
     */
    public function getSchemaName()
    {
        return $this->schemaName;
    }

    /**
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param array $tables
     */
    public function setTables(array $tables)
    {
        $this->tables = $tables;
    }

    /**
     * @return array
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * @param array $views
     */
    public function setViews(array $views)
    {
        $this->views = $views;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return array
     */
    public function getMaterializedViews()
    {
        return $this->materializedViews;
    }

    /**
     * @param array $materializedViews
     */
    public function setMaterializedViews(array $materializedViews)
    {
        $this->materializedViews = $materializedViews;
    }

    /**
     * @param array $types
     */
    public function setTypes(array $types)
    {
        $this->types = $types;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * @param array $functions
     */
    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
    }

    /**
     * @return array
     */
    public function getTriggers()
    {
        return $this->triggers;
    }

    /**
     * @param array $triggers
     */
    public function setTriggers(array $triggers)
    {
        $this->triggers = $triggers;
    }

    /**
     * @return array
     */
    public function getSequences()
    {
        return $this->sequences;
    }

    /**
     * @param array $sequences
     */
    public function setSequences(array $sequences)
    {
        $this->sequences = $sequences;
    }
}
