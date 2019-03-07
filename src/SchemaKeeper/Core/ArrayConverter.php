<?php

namespace SchemaKeeper\Core;

class ArrayConverter
{
    /**
     * @param Dump $dump
     * @return array
     */
    public function dump2Array(Dump $dump)
    {
        $keysMapping = [
            'tables' => 'getTables',
            'views' => 'getViews',
            'materialized_views' => 'getMaterializedViews',
            'types' => 'getTypes',
            'functions' => 'getFunctions',
            'triggers' => 'getTriggers',
            'sequences' => 'getSequences',
        ];

        $result = [
            'schemas' => [],
            'extensions' => $dump->getExtensions()
        ];

        foreach ($dump->getSchemas() as $schemaDump) {
            $result['schemas'][] = $schemaDump->getSchemaName();

            foreach ($keysMapping as $key => $methodName) {
                $newItems = [];

                foreach ($schemaDump->$methodName() as $itemName => $itemContent) {
                    $newItems[$schemaDump->getSchemaName().'.'.$itemName] = $itemContent;
                }

                $result[$key] = isset($result[$key]) ? $result[$key] : [];
                $result[$key] = array_merge($result[$key], $newItems);
            }
        }

        return $result;
    }
}
