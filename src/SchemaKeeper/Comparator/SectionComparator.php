<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Comparator;

final class SectionComparator
{
    public function compareSection(string $sectionName, array $expectedSection, array $actualSection): array
    {
        if ($expectedSection === $actualSection) {
            return [];
        }

        $expectedDiff = [];
        $actualDiff = [];

        foreach (array_keys($expectedSection + $actualSection) as $itemName) {
            $inExpected = array_key_exists($itemName, $expectedSection);
            $inActual = array_key_exists($itemName, $actualSection);

            if ($inExpected && $inActual) {
                if ($expectedSection[$itemName] === $actualSection[$itemName]) {
                    continue;
                }
                $expectedDiff[$itemName] = $expectedSection[$itemName];
                $actualDiff[$itemName] = $actualSection[$itemName];
            } elseif ($inExpected) {
                $expectedDiff[$itemName] = $expectedSection[$itemName];
            } else {
                $actualDiff[$itemName] = $actualSection[$itemName];
            }
        }

        $result = [
            'expected' => [],
            'actual' => [],
        ];

        if ($expectedDiff !== []) {
            $result['expected'][$sectionName] = $expectedDiff;
        }

        if ($actualDiff !== []) {
            $result['actual'][$sectionName] = $actualDiff;
        }

        return $result;
    }
}
