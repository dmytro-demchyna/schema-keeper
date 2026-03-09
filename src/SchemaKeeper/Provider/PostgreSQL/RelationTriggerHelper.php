<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Provider\PostgreSQL;

use PDO;

final class RelationTriggerHelper
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param int[] $oids
     */
    public function getAllTriggers(array $oids): array
    {
        if (!$oids) {
            return [];
        }

        $oidList = $this->buildOidList($oids);

        $sql = '
            SELECT
                t.tgrelid,
                pg_get_triggerdef(t.oid, true) AS trigdef,
                t.tgenabled
            FROM pg_catalog.pg_trigger t
            WHERE t.tgrelid IN (' . $oidList . ')
              AND NOT t.tgisinternal
            ORDER BY t.tgrelid, t.tgname
        ';

        /** @phpstan-ignore-next-line */
        $rows = $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];

        /** @phpstan-ignore-next-line */
        foreach ($rows as $row) {
            $trigdef = (string) $row['trigdef'];

            if (preg_match('/^CREATE (?:CONSTRAINT )?TRIGGER (.+)$/s', $trigdef, $matches)) {
                $key = (int) $row['tgrelid'];
                $grouped[$key][] = $matches[1] . TriggerDescriber::triggerStateLabel((string) $row['tgenabled']);
            }
        }

        return $grouped;
    }

    private function buildOidList(array $oids): string
    {
        return implode(', ', array_map('intval', $oids));
    }
}
