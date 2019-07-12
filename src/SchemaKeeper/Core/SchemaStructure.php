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
     * @var array<string, string>
     */
    private $tables = [];

    /**
     * @var array<string, string>
     */
    private $views = [];

    /**
     * @var array<string, string>
     */
    private $materializedViews = [];

    /**
     * @var array<string, string>
     */
    private $types = [];

    /**
     * @var array<string, string>
     */
    private $functions = [];

    /**
     * @var array<string, string>
     */
    private $triggers = [];

    /**
     * @var array<string, string>
     */
    private $sequences = [];


    public function __construct(string $schemaName)
    {
        $this->schemaName = $schemaName;
    }

    public function getSchemaName(): string
    {
        return $this->schemaName;
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    public function setTables(array $tables)
    {
        $this->tables = $tables;
    }

    public function getViews(): array
    {
        return $this->views;
    }

    public function setViews(array $views)
    {
        $this->views = $views;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getMaterializedViews(): array
    {
        return $this->materializedViews;
    }

    public function setMaterializedViews(array $materializedViews)
    {
        $this->materializedViews = $materializedViews;
    }

    public function setTypes(array $types)
    {
        $this->types = $types;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
    }

    public function getTriggers(): array
    {
        return $this->triggers;
    }

    public function setTriggers(array $triggers)
    {
        $this->triggers = $triggers;
    }

    public function getSequences(): array
    {
        return $this->sequences;
    }

    public function setSequences(array $sequences)
    {
        $this->sequences = $sequences;
    }
}
