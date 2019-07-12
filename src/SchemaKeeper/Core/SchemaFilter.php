<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Core;

class SchemaFilter
{
    /**
     * @param string $schemaName
     * @param array<string, string> $items
     * @return array<string, string>
     */
    public function filter(string $schemaName, array $items): array
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
