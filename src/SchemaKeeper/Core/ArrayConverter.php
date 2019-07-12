<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Core;

use SchemaKeeper\Exception\KeeperException;

/**
 * @internal
 */
class ArrayConverter
{
    /**
     * @param Dump $dump
     * @return array<string, array>
     */
    public function dump2Array(Dump $dump): array
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

        $result = array_combine(array_keys($keysMapping), array_fill(0, count($keysMapping), []));

        if ($result === false) {
            throw new KeeperException('array_combine() problem');
        }

        $result['schemas'] = [];
        $result['extensions'] = $dump->getExtensions();

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
