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
     * @return array
     */
    public function compareSection($sectionName, array $expectedSection, array $actualSection)
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

    /**
     * @param string $sectionName
     * @param array $expectedSection
     * @param array $actualSection
     * @return array
     */
    private function doCompare($sectionName, array $expectedSection, array $actualSection)
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
