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

final class MaterializedViewDescriber implements IDescriber
{
    private PDO $conn;

    private SqlHelper $sqlHelper;

    private TextTableRenderer $tableRenderer;

    public function __construct(PDO $conn, SqlHelper $sqlHelper, TextTableRenderer $tableRenderer)
    {
        $this->conn = $conn;
        $this->sqlHelper = $sqlHelper;
        $this->tableRenderer = $tableRenderer;
    }

    public function describeAll(): array
    {
        $schemaFilter = $this->sqlHelper->buildSchemaFilterCondition('n.nspname');
        $extensionFilter = $this->sqlHelper->buildExtensionObjectFilterCondition(
            'c.oid',
            'pg_class',
        );

        $sql = '
            SELECT
                n.nspname AS schema_name,
                c.relname AS matview_name,
                c.oid AS matview_oid,
                pg_get_viewdef(c.oid) AS view_definition
            FROM pg_catalog.pg_class c
            JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            WHERE c.relkind = \'m\'
              AND ' . $schemaFilter . '
              AND ' . $extensionFilter . '
            ORDER BY n.nspname, c.relname
        ';

        $stmt = $this->conn->query($sql);

        $matviewRows = [];
        $allOids = [];

        /** @phpstan-ignore-next-line */
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $matviewRows[] = $row;
            $allOids[] = (int) $row['matview_oid'];
        }

        if (!$allOids) {
            return [];
        }

        $allColumns = $this->getAllMatViewColumns($allOids);
        $allIndexes = $this->getAllMatViewIndexes($allOids);

        $matviews = [];

        foreach ($matviewRows as $row) {
            $oid = (int) $row['matview_oid'];
            $schemaName = (string) $row['schema_name'];
            $matviewName = (string) $row['matview_name'];
            $matviewPath = $schemaName . '.' . $matviewName;

            $viewDef = (string) $row['view_definition'];
            $viewDef = $viewDef ? ' ' . trim($viewDef) : '';

            $matviews[$matviewPath] = $this->describe(
                $schemaName,
                $matviewName,
                $allColumns[$oid] ?? [],
                $allIndexes[$oid] ?? [],
                $viewDef,
            );
        }

        return $matviews;
    }

    private function describe(
        string $schemaName,
        string $matviewName,
        array $columns,
        array $indexes,
        string $viewDef
    ): string {
        $headers = [' Column', 'Type', 'Collation', 'Nullable', 'Default', 'Storage', 'Stats target'];
        $rows = [];

        foreach ($columns as $col) {
            $rows[] = [
                $col['column_name'],
                $col['data_type'],
                $col['collation'] ?? '',
                $col['is_nullable'] ?? '',
                $col['column_default'] ?? '',
                $col['storage'],
                $col['stats_target'] ?? '',
            ];
        }

        $title = 'Materialized view "' . $schemaName . '.' . $matviewName . '"';
        $output = $this->tableRenderer->render($title, $headers, $rows);

        if ($indexes) {
            $output .= "Indexes:\n";

            foreach ($indexes as $idx) {
                $output .= '    ' . $idx . "\n";
            }
        }

        if ($viewDef !== '') {
            $output .= "View definition:\n";
            $output .= $viewDef . "\n";
        }

        $output .= "\n";

        return $output;
    }

    /**
     * @param int[] $oids
     */
    private function getAllMatViewColumns(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $sql = "
            SELECT
                a.attrelid,
                a.attname AS column_name,
                pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type,
                CASE
                    WHEN co.collname IS NOT NULL AND co.collname != 'default' THEN co.collname
                    ELSE ''
                END AS collation,
                '' AS is_nullable,
                '' AS column_default,
                CASE a.attstorage
                    WHEN 'p' THEN 'plain'
                    WHEN 'e' THEN 'external'
                    WHEN 'm' THEN 'main'
                    WHEN 'x' THEN 'extended'
                    ELSE ''
                END AS storage,
                CASE
                    WHEN a.attstattarget = -1 THEN ''
                    ELSE a.attstattarget::text
                END AS stats_target
            FROM pg_catalog.pg_attribute a
            LEFT JOIN pg_catalog.pg_collation co ON co.oid = a.attcollation AND a.attcollation != 0
            WHERE a.attrelid IN (" . $oidList . ')
              AND a.attnum > 0
              AND NOT a.attisdropped
            ORDER BY a.attrelid, a.attnum
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $grouped[(int) $row['attrelid']][] = $row;
        }

        return $grouped;
    }

    /**
     * @param int[] $oids
     */
    private function getAllMatViewIndexes(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $sql = '
            SELECT
                t.oid AS matview_oid,
                i.relname AS index_name,
                am.amname AS index_type,
                pg_get_indexdef(i.oid) AS index_def,
                idx.indisprimary,
                idx.indisunique,
                CASE WHEN idx.indisprimary THEN 0 ELSE 1 END AS sort_order
            FROM pg_catalog.pg_index idx
            JOIN pg_catalog.pg_class i ON i.oid = idx.indexrelid
            JOIN pg_catalog.pg_class t ON t.oid = idx.indrelid
            JOIN pg_catalog.pg_am am ON am.oid = i.relam
            WHERE t.oid IN (' . $oidList . ')
            ORDER BY t.oid, sort_order, i.relname
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        $seen = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $matviewOid = (int) $row['matview_oid'];
            $indexName = (string) $row['index_name'];
            $seenKey = $matviewOid . '.' . $indexName;

            if (isset($seen[$seenKey])) {
                continue;
            }
            $seen[$seenKey] = true;

            $line = '"' . $indexName . '"';

            if ($row['indisunique']) {
                $line .= ' UNIQUE,';
            }

            $indexDef = (string) $row['index_def'];
            $line .= ' ' . (string) $row['index_type'];

            if (preg_match('/USING\s+\w+\s+(.+)$/i', $indexDef, $matches)) {
                $line .= ' ' . $matches[1];
            }

            $grouped[$matviewOid][] = $line;
        }

        return $grouped;
    }
}
