<?php

namespace SchemaKeeper\Core;

class SectionComparator
{
    /**
     * @param string $sectionName
     * @param array $leftSection
     * @param array $rightSection
     * @return array
     */
    public function compareSection($sectionName, array $leftSection, array $rightSection)
    {
        if ($leftSection === $rightSection) {
            return [];
        }

        $result = $this->doCompare($sectionName, $leftSection, $rightSection);
        $resultInverted = $this->doCompare($sectionName, $rightSection, $leftSection);

        $result['left'] = array_merge($result['left'], $resultInverted['right']);
        $result['right'] = array_merge($result['right'], $resultInverted['left']);

        return $result;
    }

    /**
     * @param string $sectionName
     * @param array $leftSection
     * @param array $rightSection
     * @return array
     */
    private function doCompare($sectionName, array $leftSection, array $rightSection)
    {
        $result = [
            'left' => [],
            'right' => [],
        ];

        foreach ($leftSection as $leftItemName => $leftItemContent) {
            $rightItemContent = isset($rightSection[$leftItemName]) ? $rightSection[$leftItemName] : '';
            if ($leftItemContent === $rightItemContent) {
                continue;
            }

            $result['left'][$sectionName][$leftItemName] = $leftSection[$leftItemName];
            if ($rightItemContent) {
                $result['right'][$sectionName][$leftItemName] = $rightItemContent;
            }
        }

        return $result;
    }
}
