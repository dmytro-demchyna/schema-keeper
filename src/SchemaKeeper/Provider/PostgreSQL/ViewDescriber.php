<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Provider\PostgreSQL;

use PDO;
use SchemaKeeper\Dto\Section;
use SchemaKeeper\Provider\IDescriber;

final class ViewDescriber implements IDescriber
{
    private PDO $conn;

    private SqlHelper $sqlHelper;

    private TextTableRenderer $tableRenderer;

    private RelationTriggerHelper $triggerHelper;

    /**
     * @var string[]
     */
    private array $skippedSectionNames;

    public function __construct(
        PDO $conn,
        SqlHelper $sqlHelper,
        TextTableRenderer $tableRenderer,
        RelationTriggerHelper $triggerHelper,
        array $skippedSectionNames = []
    ) {
        $this->conn = $conn;
        $this->sqlHelper = $sqlHelper;
        $this->tableRenderer = $tableRenderer;
        $this->triggerHelper = $triggerHelper;
        $this->skippedSectionNames = $skippedSectionNames;
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
                c.relname AS view_name,
                c.oid AS view_oid,
                pg_get_viewdef(c.oid, true) AS view_definition,
                c.reloptions
            FROM pg_catalog.pg_class c
            JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            WHERE c.relkind = \'v\'
              AND ' . $schemaFilter . '
              AND ' . $extensionFilter . '
            ORDER BY n.nspname, c.relname
        ';

        $stmt = $this->conn->query($sql);

        $viewRows = [];
        $allOids = [];

        /** @phpstan-ignore-next-line */
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $viewRows[] = $row;
            $allOids[] = (int) $row['view_oid'];
        }

        if (!$allOids) {
            return [];
        }

        $allColumns = $this->getAllViewColumns($allOids);

        $allTriggers = [];

        if (!in_array(Section::TRIGGERS, $this->skippedSectionNames, true)) {
            $allTriggers = $this->triggerHelper->getAllTriggers($allOids);
        }

        $views = [];

        foreach ($viewRows as $row) {
            $oid = (int) $row['view_oid'];
            $schemaName = (string) $row['schema_name'];
            $viewName = (string) $row['view_name'];
            $viewPath = $schemaName . '.' . $viewName;

            $viewDef = (string) $row['view_definition'];
            $viewDef = $viewDef ? $viewDef . "\n" : '';

            $options = '';

            if ($row['reloptions']) {
                $options = trim((string) $row['reloptions'], '{}');
            }

            $views[$viewPath] = $this->describe(
                $schemaName,
                $viewName,
                $allColumns[$oid] ?? [],
                $viewDef,
                $allTriggers[$oid] ?? [],
                $options,
            );
        }

        return $views;
    }

    private function describe(
        string $schemaName,
        string $viewName,
        array $columns,
        string $viewDef,
        array $triggers,
        string $options
    ): string {
        $headers = [' Column', 'Type', 'Collation', 'Nullable', 'Default', 'Storage'];
        $rows = [];

        foreach ($columns as $col) {
            $rows[] = [
                $col['column_name'],
                $col['data_type'],
                $col['collation'] ?? '',
                $col['is_nullable'] ?? '',
                $col['column_default'] ?? '',
                $col['storage'],
            ];
        }

        $title = 'View "' . $schemaName . '.' . $viewName . '"';
        $output = $this->tableRenderer->render($title, $headers, $rows);

        if ($viewDef !== '') {
            $output .= "View definition:\n";
            $output .= $viewDef;
        }

        if ($triggers) {
            $output .= "Triggers:\n";

            foreach ($triggers as $trigger) {
                $output .= '    ' . $trigger . "\n";
            }
        }

        if ($options !== '') {
            $output .= 'Options: ' . $options . "\n";
        }

        $output .= "\n";

        return $output;
    }

    /**
     * @param int[] $oids
     */
    private function getAllViewColumns(array $oids): array
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
                CASE WHEN a.attnotnull THEN 'not null' ELSE '' END AS is_nullable,
                COALESCE(pg_catalog.pg_get_expr(d.adbin, d.adrelid), '') AS column_default,
                CASE a.attstorage
                    WHEN 'p' THEN 'plain'
                    WHEN 'e' THEN 'external'
                    WHEN 'm' THEN 'main'
                    WHEN 'x' THEN 'extended'
                    ELSE ''
                END AS storage
            FROM pg_catalog.pg_attribute a
            LEFT JOIN pg_catalog.pg_attrdef d ON d.adrelid = a.attrelid AND d.adnum = a.attnum
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
}
