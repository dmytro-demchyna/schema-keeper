<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Cli;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

final class DiffBuilder
{
    private Differ $differ;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct()
    {
        $builder = new UnifiedDiffOutputBuilder('', false);
        $this->differ = new Differ($builder);
    }

    public function format(array $expected, array $actual): string
    {
        $sections = array_unique(array_merge(array_keys($expected), array_keys($actual)));
        sort($sections);

        $parts = [];

        foreach ($sections as $section) {
            $expectedItems = $expected[$section] ?? [];
            $actualItems = $actual[$section] ?? [];

            $itemNames = array_unique(array_merge(array_keys($expectedItems), array_keys($actualItems)));
            sort($itemNames);

            /** @psalm-suppress NoValue */
            foreach ($itemNames as $itemName) {
                $itemName = (string) $itemName;
                $hasExpected = array_key_exists($itemName, $expectedItems);
                $hasActual = array_key_exists($itemName, $actualItems);

                $fromLabel = $hasExpected ? $section . '/' . $itemName : '/dev/null';
                $toLabel = $hasActual ? $section . '/' . $itemName : '/dev/null';
                $from = $hasExpected ? $expectedItems[$itemName] . "\n" : '';
                $to = $hasActual ? $actualItems[$itemName] . "\n" : '';

                $header = '--- ' . $fromLabel . PHP_EOL . '+++ ' . $toLabel . PHP_EOL;
                $diff = $this->differ->diff($from, $to);

                $parts[] = $header . rtrim($diff);
            }
        }

        return implode(PHP_EOL, $parts);
    }
}
