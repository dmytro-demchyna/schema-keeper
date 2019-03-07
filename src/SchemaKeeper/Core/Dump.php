<?php

namespace SchemaKeeper\Core;

class Dump
{
    /**
     * @var SchemaStructure[]
     */
    private $schemas;

    /**
     * @var string[]
     */
    private $extensions;

    /**
     * @param SchemaStructure[] $schemas
     * @param string[] $extensions
     */
    public function __construct(array $schemas, array $extensions)
    {
        $this->schemas = $schemas;
        $this->extensions = $extensions;
    }

    /**
     * @return SchemaStructure[]
     */
    public function getSchemas()
    {
        return $this->schemas;
    }

    /**
     * @return string[]
     */
    public function getExtensions()
    {
        return $this->extensions;
    }
}
