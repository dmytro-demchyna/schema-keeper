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

final class TypeDescriber implements IDescriber
{
    private PDO $conn;

    private SqlHelper $sqlHelper;

    private TextTableRenderer $tableRenderer;

    private ConstraintHelper $constraintHelper;

    public function __construct(
        PDO $conn,
        SqlHelper $sqlHelper,
        TextTableRenderer $tableRenderer,
        ConstraintHelper $constraintHelper
    ) {
        $this->conn = $conn;
        $this->sqlHelper = $sqlHelper;
        $this->tableRenderer = $tableRenderer;
        $this->constraintHelper = $constraintHelper;
    }

    /**
     * @return string[]
     */
    public function describeAll(): array
    {
        $schemaFilter = $this->sqlHelper->buildSchemaFilterCondition('n.nspname');
        $extensionFilter = $this->sqlHelper->buildExtensionObjectFilterCondition(
            't.oid',
            'pg_type',
        );

        $sql = "
            SELECT
                   concat_ws('.', n.nspname, t.typname) AS type_path,
                   n.nspname AS schema_name,
                   t.typname AS type_name,
                   t.typtype,
                   t.oid AS type_oid
             FROM pg_catalog.pg_type t
             LEFT JOIN pg_catalog.pg_namespace n ON n.oid = t.typnamespace
             WHERE (t.typrelid = 0 OR (SELECT c.relkind = 'c'
                                       FROM pg_catalog.pg_class c
                                       WHERE c.oid = t.typrelid))
                   AND NOT EXISTS(SELECT 1
                                  FROM pg_catalog.pg_type el
                                  WHERE el.oid = t.typelem AND el.typarray = t.oid)
                   AND t.typtype != 'm'
                   AND (" . $schemaFilter . ')
                   AND (' . $extensionFilter . ')
              ORDER BY type_path
        ';

        $stmt = $this->conn->query($sql);

        $typeRows = [];
        $enumOids = [];
        $compositeOids = [];
        $domainOids = [];
        $rangeOids = [];

        /** @phpstan-ignore-next-line */
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $typeRows[] = $row;
            $oid = (int) $row['type_oid'];
            $typType = (string) $row['typtype'];

            if ($typType === 'e') {
                $enumOids[] = $oid;
            } elseif ($typType === 'd') {
                $domainOids[] = $oid;
            } elseif ($typType === 'r') {
                $rangeOids[] = $oid;
            } else {
                $compositeOids[] = $oid;
            }
        }

        if (!$typeRows) {
            return [];
        }

        $allEnums = $this->getAllEnums($enumOids);
        $allComposites = $this->getAllCompositeColumns($compositeOids);
        $allDomains = $this->getAllDomains($domainOids);
        $allDomainConstraints = $this->constraintHelper->getAllConstraintsByType($domainOids);
        $allRanges = $this->getAllRanges($rangeOids);

        $actualTypes = [];

        foreach ($typeRows as $row) {
            $typePath = (string) $row['type_path'];
            $schemaName = (string) $row['schema_name'];
            $typeName = (string) $row['type_name'];
            $typType = (string) $row['typtype'];
            $oid = (int) $row['type_oid'];

            if ($typType === 'e') {
                $definition = $allEnums[$oid] ?? '';
            } elseif ($typType === 'd') {
                $definition = $this->formatDomain(
                    $schemaName,
                    $typeName,
                    $allDomains[$oid] ?? null,
                    $allDomainConstraints[$oid] ?? [],
                );
            } elseif ($typType === 'r') {
                $definition = $this->formatRange(
                    $schemaName,
                    $typeName,
                    $allRanges[$oid] ?? null,
                );
            } else {
                $definition = $this->formatComposite(
                    $schemaName,
                    $typeName,
                    $allComposites[$oid] ?? [],
                );
            }

            $actualTypes[$typePath] = $definition;
        }

        return $actualTypes;
    }

    /**
     * @param int[] $oids
     */
    private function getAllEnums(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $sql = '
            SELECT
                enumtypid,
                array_agg(enumlabel ORDER BY enumsortorder) AS labels
            FROM pg_catalog.pg_enum
            WHERE enumtypid IN (' . $oidList . ')
            GROUP BY enumtypid
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $result[(int) $row['enumtypid']] = (string) $row['labels'];
        }

        return $result;
    }

    /**
     * @param int[] $oids
     */
    private function getAllCompositeColumns(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $sql = "
            SELECT
                t.oid AS type_oid,
                a.attname AS column_name,
                pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type,
                CASE
                    WHEN co.collname IS NOT NULL AND co.collname != 'default' THEN co.collname
                    ELSE ''
                END AS collation,
                '' AS is_nullable,
                '' AS column_default
            FROM pg_catalog.pg_attribute a
            JOIN pg_catalog.pg_type t ON t.typrelid = a.attrelid
            LEFT JOIN pg_catalog.pg_collation co ON co.oid = a.attcollation AND a.attcollation != 0
            WHERE t.oid IN (" . $oidList . ')
              AND a.attnum > 0
              AND NOT a.attisdropped
            ORDER BY t.oid, a.attnum
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $grouped[(int) $row['type_oid']][] = $row;
        }

        return $grouped;
    }

    /**
     * @param int[] $oids
     */
    private function getAllDomains(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $sql = "
            SELECT
                t.oid AS type_oid,
                pg_catalog.format_type(t.typbasetype, t.typtypmod) AS base_type,
                t.typnotnull,
                t.typdefault,
                CASE
                    WHEN co.collname IS NOT NULL AND co.collname != 'default' THEN co.collname
                    ELSE ''
                END AS collation
            FROM pg_catalog.pg_type t
            LEFT JOIN pg_catalog.pg_collation co ON co.oid = t.typcollation AND t.typcollation != 0
            WHERE t.oid IN (" . $oidList . ')
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $result[(int) $row['type_oid']] = $row;
        }

        return $result;
    }

    /**
     * @param int[] $oids
     */
    private function getAllRanges(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = SqlHelper::buildOidList($oids);

        $sql = "
            SELECT
                r.rngtypid AS type_oid,
                pg_catalog.format_type(r.rngsubtype, NULL) AS subtype,
                opc.opcname AS opclass,
                CASE WHEN r.rngcollation != 0 THEN co.collname ELSE '' END AS collation,
                CASE WHEN r.rngcanonical != 0 THEN p_can.proname ELSE '' END AS canonical,
                CASE WHEN r.rngsubdiff != 0 THEN p_diff.proname ELSE '' END AS subtype_diff
            FROM pg_catalog.pg_range r
            JOIN pg_catalog.pg_opclass opc ON opc.oid = r.rngsubopc
            LEFT JOIN pg_catalog.pg_collation co ON co.oid = r.rngcollation
            LEFT JOIN pg_catalog.pg_proc p_can ON p_can.oid = r.rngcanonical
            LEFT JOIN pg_catalog.pg_proc p_diff ON p_diff.oid = r.rngsubdiff
            WHERE r.rngtypid IN (" . $oidList . ')
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $result[(int) $row['type_oid']] = $row;
        }

        return $result;
    }

    private function formatComposite(string $schemaName, string $typeName, array $columns): string
    {
        if (empty($columns)) {
            return '';
        }

        $headers = [' Column', 'Type', 'Collation', 'Nullable', 'Default'];
        $rows = [];

        foreach ($columns as $col) {
            $rows[] = [
                $col['column_name'],
                $col['data_type'],
                $col['collation'] ?? '',
                $col['is_nullable'] ?? '',
                $col['column_default'] ?? '',
            ];
        }

        $title = 'Composite type "' . $schemaName . '.' . $typeName . '"';

        return $this->tableRenderer->render($title, $headers, $rows) . "\n";
    }

    private function formatDomain(
        string $schemaName,
        string $typeName,
        ?array $domainRow,
        array $constraints
    ): string {
        if (!$domainRow) {
            return '';
        }

        $output = 'Domain "' . $schemaName . '.' . $typeName . '"' . "\n";
        $output .= 'Base type: ' . (string) $domainRow['base_type'] . "\n";

        if ((string) $domainRow['collation'] !== '') {
            $output .= 'Collation: ' . (string) $domainRow['collation'] . "\n";
        }

        if ($domainRow['typnotnull']) {
            $output .= "Not null: true\n";
        }

        if ($domainRow['typdefault'] !== null) {
            $output .= 'Default: ' . (string) $domainRow['typdefault'] . "\n";
        }

        if ($constraints) {
            $output .= "Check constraints:\n";

            foreach ($constraints as $con) {
                $output .= '    ' . $con . "\n";
            }
        }

        $output .= "\n";

        return $output;
    }

    private function formatRange(string $schemaName, string $typeName, ?array $rangeRow): string
    {
        if (!$rangeRow) {
            return '';
        }

        $output = 'Range type "' . $schemaName . '.' . $typeName . '"' . "\n";
        $output .= 'Subtype: ' . (string) $rangeRow['subtype'] . "\n";
        $output .= 'Subtype opclass: ' . (string) $rangeRow['opclass'] . "\n";

        if ((string) $rangeRow['collation'] !== '') {
            $output .= 'Collation: ' . (string) $rangeRow['collation'] . "\n";
        }

        if ((string) $rangeRow['canonical'] !== '') {
            $output .= 'Canonical: ' . (string) $rangeRow['canonical'] . "\n";
        }

        if ((string) $rangeRow['subtype_diff'] !== '') {
            $output .= 'Subtype diff: ' . (string) $rangeRow['subtype_diff'] . "\n";
        }

        $output .= "\n";

        return $output;
    }
}
