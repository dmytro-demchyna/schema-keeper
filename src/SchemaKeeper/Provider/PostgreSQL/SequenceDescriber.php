<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Provider\PostgreSQL;

use PDO;
use SchemaKeeper\Provider\IDescriber;

final class SequenceDescriber implements IDescriber
{
    private PDO $conn;

    private SqlHelper $sqlHelper;

    public function __construct(PDO $conn, SqlHelper $sqlHelper)
    {
        $this->conn = $conn;
        $this->sqlHelper = $sqlHelper;
    }

    /**
     * @return string[]
     */
    public function describeAll(): array
    {
        $schemaFilter = $this->sqlHelper->buildSchemaFilterCondition('n.nspname');
        $extensionFilter = $this->sqlHelper->buildExtensionObjectFilterCondition(
            'c.oid',
            'pg_class',
        );

        $sql = "
            SELECT
                n.nspname || '.' || c.relname AS seq_path,
                format_type(s.seqtypid, NULL) AS data_type,
                s.seqstart::text AS start_value,
                s.seqmin::text AS minimum_value,
                s.seqmax::text AS maximum_value,
                s.seqincrement::text AS increment,
                CASE WHEN s.seqcycle THEN 'YES' ELSE 'NO' END AS cycle_option,
                s.seqcache::text AS cache_size
            FROM pg_catalog.pg_sequence s
            JOIN pg_catalog.pg_class c ON c.oid = s.seqrelid
            JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            WHERE " . $schemaFilter . '
              AND ' . $extensionFilter . '
            ORDER BY seq_path
        ';

        $stmt = $this->conn->query($sql);

        $actualSequences = [];

        /** @phpstan-ignore-next-line */
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sequence = (string) $row['seq_path'];
            $actualSequences[$sequence] = $this->formatSequence($row);
        }

        return $actualSequences;
    }

    private function formatSequence(array $row): string
    {
        $output = 'Sequence "' . (string) $row['seq_path'] . '"' . "\n";
        $output .= 'Data type: ' . (string) $row['data_type'] . "\n";
        $output .= 'Start value: ' . (string) $row['start_value'] . "\n";
        $output .= 'Min value: ' . (string) $row['minimum_value'] . "\n";
        $output .= 'Max value: ' . (string) $row['maximum_value'] . "\n";
        $output .= 'Increment: ' . (string) $row['increment'] . "\n";
        $output .= 'Cycle: ' . (string) $row['cycle_option'] . "\n";
        $output .= 'Cache size: ' . (string) $row['cache_size'] . "\n";

        return $output;
    }
}
