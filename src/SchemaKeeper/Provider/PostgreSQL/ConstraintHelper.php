<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Provider\PostgreSQL;

use PDO;

final class ConstraintHelper
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param int[] $oids
     */
    public function getAllConstraintsByRelation(array $oids, string $contype): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = $this->buildOidList($oids);

        $sql = '
            SELECT
                conrelid,
                conname,
                pg_get_constraintdef(oid) AS condef
            FROM pg_catalog.pg_constraint
            WHERE conrelid IN (' . $oidList . ')
              AND contype = :contype
            ORDER BY conrelid, conname
        ';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['contype' => $contype]);

        /** @phpstan-ignore-next-line */
        return $this->groupByColumn($stmt->fetchAll(PDO::FETCH_ASSOC), 'conrelid');
    }

    /**
     * @param int[] $oids
     */
    public function getAllConstraintsByType(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = $this->buildOidList($oids);

        $sql = '
            SELECT
                c.contypid,
                conname,
                pg_get_constraintdef(c.oid) AS condef
            FROM pg_catalog.pg_constraint c
            WHERE c.contypid IN (' . $oidList . ')
            ORDER BY c.contypid, conname
        ';

        /** @phpstan-ignore-next-line */
        return $this->groupByColumn($this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC), 'contypid');
    }

    private function buildOidList(array $oids): string
    {
        return implode(', ', array_map('intval', $oids));
    }

    private function groupByColumn(array $rows, string $column): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $key = (int) $row[$column];
            $grouped[$key][] = '"' . (string) $row['conname'] . '" ' . (string) $row['condef'];
        }

        return $grouped;
    }
}
