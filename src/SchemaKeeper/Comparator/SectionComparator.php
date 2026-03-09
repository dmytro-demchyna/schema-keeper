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

        $result = $this->doCompare($sectionName, $expectedSection, $actualSection);
        $resultInverted = $this->doCompare($sectionName, $actualSection, $expectedSection);

        $result['expected'] = array_merge($result['expected'], $resultInverted['actual']);
        $result['actual'] = array_merge($result['actual'], $resultInverted['expected']);

        return $result;
    }

    private function doCompare(string $sectionName, array $expectedSection, array $actualSection): array
    {
        $result = [
            'expected' => [],
            'actual' => [],
        ];

        foreach ($expectedSection as $expectedItemName => $expectedItemContent) {
            if (array_key_exists($expectedItemName, $actualSection)) {
                if ($expectedItemContent === $actualSection[$expectedItemName]) {
                    continue;
                }
                $result['expected'][$sectionName][$expectedItemName] = $expectedItemContent;
                $result['actual'][$sectionName][$expectedItemName] = $actualSection[$expectedItemName];
            } else {
                $result['expected'][$sectionName][$expectedItemName] = $expectedItemContent;
            }
        }

        return $result;
    }
}
