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

final class TableDescriber implements IDescriber
{
    private PDO $conn;

    private SqlHelper $sqlHelper;

    private TextTableRenderer $tableRenderer;

    private RelationTriggerHelper $triggerHelper;

    private ConstraintHelper $constraintHelper;

    /**
     * @var string[]
     */
    private array $skippedSectionNames;

    public function __construct(
        PDO $conn,
        SqlHelper $sqlHelper,
        TextTableRenderer $tableRenderer,
        RelationTriggerHelper $triggerHelper,
        ConstraintHelper $constraintHelper,
        array $skippedSectionNames = []
    ) {
        $this->conn = $conn;
        $this->sqlHelper = $sqlHelper;
        $this->tableRenderer = $tableRenderer;
        $this->triggerHelper = $triggerHelper;
        $this->constraintHelper = $constraintHelper;
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
                c.relname AS table_name,
                c.oid AS table_oid,
                c.relkind,
                c.relispartition
            FROM pg_catalog.pg_class c
            JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            WHERE c.relkind IN (\'r\', \'p\')
              AND ' . $schemaFilter . '
              AND ' . $extensionFilter . '
            ORDER BY n.nspname, c.relname
        ';

        $stmt = $this->conn->query($sql);

        $tableRows = [];
        $allOids = [];
        $childOids = [];
        $parentOids = [];

        /** @phpstan-ignore-next-line */
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tableRows[] = $row;
            $oid = (int) $row['table_oid'];
            $allOids[] = $oid;

            if ($row['relispartition']) {
                $childOids[] = $oid;
            }

            if ((string) $row['relkind'] === 'p') {
                $parentOids[] = $oid;
            }
        }

        if (!$allOids) {
            return [];
        }

        $allColumns = $this->getAllColumns($allOids);
        $allIndexes = $this->getAllIndexes($allOids);
        $allCheckConstraints = $this->constraintHelper->getAllConstraintsByRelation($allOids, 'c');
        $allForeignKeys = $this->constraintHelper->getAllConstraintsByRelation($allOids, 'f');
        $allReferencedBy = $this->getAllReferencedBy($allOids);

        $allTriggers = [];

        if (!in_array(Section::TRIGGERS, $this->skippedSectionNames, true)) {
            $allTriggers = $this->triggerHelper->getAllTriggers($allOids);
        }

        $allPartitionOf = $this->getAllPartitionOf($childOids);
        $allPartitionKeys = $this->getAllPartitionKeys($parentOids);
        $allPartitions = $this->getAllPartitions($parentOids);

        $tables = [];

        foreach ($tableRows as $row) {
            $oid = (int) $row['table_oid'];
            $schemaName = (string) $row['schema_name'];
            $tableName = (string) $row['table_name'];
            $tablePath = $schemaName . '.' . $tableName;
            $relkind = (string) $row['relkind'];
            $relispartition = (bool) $row['relispartition'];

            $tables[$tablePath] = $this->describe(
                $schemaName,
                $tableName,
                $oid,
                $relkind,
                $relispartition,
                $allColumns[$oid] ?? [],
                $allIndexes[$oid] ?? [],
                $allCheckConstraints[$oid] ?? [],
                $allForeignKeys[$oid] ?? [],
                $allReferencedBy[$oid] ?? [],
                $allTriggers[$oid] ?? [],
                $allPartitionOf[$oid] ?? '',
                $allPartitionKeys[$oid] ?? '',
                $allPartitions[$oid] ?? [],
            );
        }

        return $tables;
    }

    private function describe(
        string $schemaName,
        string $tableName,
        int $tableOid,
        string $relkind,
        bool $relispartition,
        array $columns,
        array $indexes,
        array $checkConstraints,
        array $foreignKeys,
        array $referencedBy,
        array $triggers,
        string $partitionOf,
        string $partitionKey,
        array $partitions
    ): string {
        $headers = [' Column', 'Type', 'Collation', 'Nullable', 'Default'];
        $rows = [];

        foreach ($columns as $col) {
            $rows[] = [
                $col['column_name'],
                $col['data_type'],
                $col['collation'] ?? '',
                $col['is_nullable'],
                $col['column_default'] ?? '',
            ];
        }

        $title = 'Table "' . $schemaName . '.' . $tableName . '"';
        $output = $this->tableRenderer->render($title, $headers, $rows);

        if ($relispartition && $partitionOf !== '') {
            $output .= 'Partition of: ' . $partitionOf . "\n";
        }

        if ($relkind === 'p' && $partitionKey !== '') {
            $output .= 'Partition key: ' . $partitionKey . "\n";
        }

        if ($indexes) {
            $output .= "Indexes:\n";

            foreach ($indexes as $idx) {
                $output .= '    ' . $idx . "\n";
            }
        }

        if ($checkConstraints) {
            $output .= "Check constraints:\n";

            foreach ($checkConstraints as $con) {
                $output .= '    ' . $con . "\n";
            }
        }

        if ($foreignKeys) {
            $output .= "Foreign-key constraints:\n";

            foreach ($foreignKeys as $fk) {
                $output .= '    ' . $fk . "\n";
            }
        }

        if ($referencedBy) {
            $output .= "Referenced by:\n";

            foreach ($referencedBy as $ref) {
                $output .= '    ' . $ref . "\n";
            }
        }

        if ($triggers) {
            $output .= "Triggers:\n";

            foreach ($triggers as $trigger) {
                $output .= '    ' . $trigger . "\n";
            }
        }

        if ($relkind === 'p' && $partitions) {
            $output .= "Partitions:\n";

            foreach ($partitions as $partition) {
                $output .= '    ' . $partition . "\n";
            }
        }

        $output .= "\n";

        return $output;
    }

    /**
     * @param int[] $oids
     */
    private function getAllColumns(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $generatedClause = '';

        if ($this->sqlHelper->getServerVersionNum() >= SqlHelper::PG_VERSION_12) {
            $generatedClause = "WHEN a.attgenerated = 's' THEN "
                . "'generated always as (' || pg_catalog.pg_get_expr(d.adbin, d.adrelid) || ') stored'";
        }

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
                CASE
                    WHEN a.attidentity = 'a' THEN 'generated always as identity'
                    WHEN a.attidentity = 'd' THEN 'generated by default as identity'
                    $generatedClause
                    ELSE COALESCE(pg_catalog.pg_get_expr(d.adbin, d.adrelid), '')
                END AS column_default
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

    /**
     * @param int[] $oids
     */
    private function getAllIndexes(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $sql = "
            SELECT
                t.oid AS table_oid,
                i.relname AS index_name,
                am.amname AS index_type,
                pg_get_indexdef(i.oid) AS index_def,
                idx.indisprimary,
                idx.indisunique,
                con.contype,
                CASE WHEN con.contype = 'u' THEN true ELSE false END AS is_unique_constraint,
                CASE WHEN con.contype = 'x' THEN true ELSE false END AS is_exclusion,
                CASE WHEN con.contype = 'x' THEN pg_get_constraintdef(con.oid) ELSE NULL END AS exclusion_def,
                COALESCE(con.condeferrable, false) AS is_deferrable,
                COALESCE(con.condeferred, false) AS is_deferred,
                CASE WHEN idx.indisprimary THEN 0 ELSE 1 END AS sort_order
            FROM pg_catalog.pg_index idx
            JOIN pg_catalog.pg_class i ON i.oid = idx.indexrelid
            JOIN pg_catalog.pg_class t ON t.oid = idx.indrelid
            JOIN pg_catalog.pg_am am ON am.oid = i.relam
            LEFT JOIN pg_catalog.pg_constraint con ON con.conindid = i.oid AND con.contype IN ('p', 'u', 'x')
            WHERE t.oid IN (" . $oidList . ')
            ORDER BY t.oid, sort_order, i.relname
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        $seen = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $tableOid = (int) $row['table_oid'];
            $indexName = (string) $row['index_name'];
            $seenKey = $tableOid . '.' . $indexName;

            if (isset($seen[$seenKey])) {
                continue;
            }
            $seen[$seenKey] = true;

            $line = '"' . $indexName . '"';

            if ($row['indisprimary']) {
                $line .= ' PRIMARY KEY,';
            } elseif ($row['is_exclusion']) {
                $line .= ' ' . (string) $row['exclusion_def'];
                $grouped[$tableOid][] = $line;

                continue;
            } elseif ($row['indisunique']) {
                if ($row['is_unique_constraint']) {
                    $line .= ' UNIQUE CONSTRAINT,';
                } else {
                    $line .= ' UNIQUE,';
                }
            }

            $indexDef = (string) $row['index_def'];
            $line .= ' ' . (string) $row['index_type'];

            if (preg_match('/USING\s+\w+\s+(.+)$/i', $indexDef, $matches)) {
                $line .= ' ' . $matches[1];
            }

            if ($row['is_deferrable']) {
                $line .= ' DEFERRABLE';

                if ($row['is_deferred']) {
                    $line .= ' INITIALLY DEFERRED';
                } else {
                    $line .= ' INITIALLY IMMEDIATE';
                }
            }

            $grouped[$tableOid][] = $line;
        }

        return $grouped;
    }

    /**
     * @param int[] $oids
     */
    private function getAllReferencedBy(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);
        $schemaFilter = $this->sqlHelper->buildSchemaFilterCondition('n.nspname');

        $sql = '
            SELECT
                con.confrelid,
                n.nspname AS schema_name,
                c.relname AS table_name,
                con.conname,
                pg_get_constraintdef(con.oid) AS condef
            FROM pg_catalog.pg_constraint con
            JOIN pg_catalog.pg_class c ON c.oid = con.conrelid
            JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            WHERE con.confrelid IN (' . $oidList . ")
              AND con.contype = 'f'
              AND " . $schemaFilter . '
            ORDER BY con.confrelid, n.nspname, c.relname, con.conname
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $key = (int) $row['confrelid'];
            $grouped[$key][] = 'TABLE "' . (string) $row['schema_name'] . '"."' . (string) $row['table_name']
                . '" CONSTRAINT "' . (string) $row['conname'] . '" ' . (string) $row['condef'];
        }

        return $grouped;
    }

    /**
     * @param int[] $oids
     */
    private function getAllPartitionOf(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $sql = '
            SELECT
                c.oid AS child_oid,
                pn.nspname || \'.\' || pc.relname AS parent_name,
                pg_catalog.pg_get_expr(c.relpartbound, c.oid) AS partition_bound
            FROM pg_catalog.pg_class c
            JOIN pg_catalog.pg_inherits i ON i.inhrelid = c.oid
            JOIN pg_catalog.pg_class pc ON pc.oid = i.inhparent
            JOIN pg_catalog.pg_namespace pn ON pn.oid = pc.relnamespace
            WHERE c.oid IN (' . $oidList . ')
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $result[(int) $row['child_oid']] = (string) $row['parent_name'] . ' ' . (string) $row['partition_bound'];
        }

        return $result;
    }

    /**
     * @param int[] $oids
     */
    private function getAllPartitionKeys(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $sql = '
            SELECT
                t.oid,
                pg_catalog.pg_get_partkeydef(t.oid) AS partition_key
            FROM pg_catalog.pg_class t
            WHERE t.oid IN (' . $oidList . ')
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $result[(int) $row['oid']] = (string) $row['partition_key'];
        }

        return $result;
    }

    /**
     * @param int[] $oids
     */
    private function getAllPartitions(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);
        $schemaFilter = $this->sqlHelper->buildSchemaFilterCondition('cn.nspname');

        $sql = '
            SELECT
                i.inhparent AS parent_oid,
                cn.nspname || \'.\' || cc.relname AS partition_name,
                pg_catalog.pg_get_expr(cc.relpartbound, cc.oid) AS partition_bound
            FROM pg_catalog.pg_inherits i
            JOIN pg_catalog.pg_class cc ON cc.oid = i.inhrelid
            JOIN pg_catalog.pg_namespace cn ON cn.oid = cc.relnamespace
            WHERE i.inhparent IN (' . $oidList . ')
              AND ' . $schemaFilter . '
            ORDER BY i.inhparent, cn.nspname, cc.relname
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $key = (int) $row['parent_oid'];
            $grouped[$key][] = (string) $row['partition_name'] . ' ' . (string) $row['partition_bound'];
        }

        return $grouped;
    }
}
