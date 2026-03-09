<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Provider\PostgreSQL;

final class TextTableRenderer
{
    /**
     * @param string[] $headers
     * @param string[][] $rows
     */
    public function render(string $title, array $headers, array $rows): string
    {
        $colWidths = $this->calculateColumnWidths($headers, $rows);

        $output = '';

        $totalWidth = array_sum($colWidths) + (count($colWidths) - 1) * 3;
        $padding = max(0, (int) (($totalWidth - strlen($title)) / 2));
        $output .= str_repeat(' ', $padding) . $title . "\n";

        $headerLine = '';
        $separatorLine = '';

        foreach ($headers as $i => $header) {
            $width = $colWidths[$i];
            $headerLine .= str_pad($header, $width);
            $separatorLine .= str_repeat('-', $width);

            if ($i < count($headers) - 1) {
                $headerLine .= ' | ';
                $separatorLine .= '-+-';
            }
        }
        $output .= $headerLine . "\n";
        $output .= $separatorLine . "\n";

        foreach ($rows as $row) {
            $line = '';

            foreach ($row as $i => $value) {
                $displayValue = ($i === 0) ? ' ' . $value : $value;
                $line .= str_pad($displayValue, $colWidths[$i]);

                if ($i < count($row) - 1) {
                    $line .= ' | ';
                }
            }
            $output .= $line . "\n";
        }

        return $output;
    }

    private function calculateColumnWidths(array $headers, array $rows): array
    {
        $widths = array_map('strlen', $headers);

        foreach ($rows as $row) {
            foreach ($row as $i => $value) {
                $widths[$i] = max($widths[$i], strlen($value) + ($i === 0 ? 1 : 0));
            }
        }

        return $widths;
    }
}
