<?php

namespace SchemaKeeper\Core;

class SchemaFilter
{
    /**
     * @param string $schemaName
     * @param array $items
     * @return array
     */
    public function filter($schemaName, array $items)
    {
        $filteredItems = [];
        foreach ($items as $itemName => $itemContent) {
            $schemaNameLength = strlen($schemaName);
            if (substr($itemName, 0, $schemaNameLength + 1) == $schemaName.'.') {
                $newName = substr($itemName, $schemaNameLength + 1);
                $filteredItems[$newName] = $itemContent;
            }
        }

        return $filteredItems;
    }
}
