<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Core;

class SectionComparator
{
    /**
     * @param string $sectionName
     * @param array $expectedSection
     * @param array $actualSection
     * @return array{expected:array,actual:array}
     */
    public function compareSection(string $sectionName, array $expectedSection, array $actualSection): array
    {
        if ($expectedSection === $actualSection) {
            return ['expected' => [], 'actual' => []];
        }

        $compared = $this->doCompare($sectionName, $expectedSection, $actualSection);
        $comparedInverted = $this->doCompare($sectionName, $actualSection, $expectedSection);

        $result = [
            'expected' => array_merge($compared['expected'], $comparedInverted['actual']),
            'actual' => array_merge($compared['actual'], $comparedInverted['expected']),
        ];

        return $result;
    }

    /**
     * @param string $sectionName
     * @param array $expectedSection
     * @param array $actualSection
     * @return array
     */
    private function doCompare(string $sectionName, array $expectedSection, array $actualSection): array
    {
        $result = [
            'expected' => [],
            'actual' => [],
        ];

        foreach ($expectedSection as $expectedItemName => $expectedItemContent) {
            $actualItemContent = isset($actualSection[$expectedItemName]) ? $actualSection[$expectedItemName] : '';
            if ($expectedItemContent === $actualItemContent) {
                continue;
            }

            $result['expected'][$sectionName][$expectedItemName] = $expectedSection[$expectedItemName];
            if ($actualItemContent) {
                $result['actual'][$sectionName][$expectedItemName] = $actualItemContent;
            }
        }

        return $result;
    }
}
