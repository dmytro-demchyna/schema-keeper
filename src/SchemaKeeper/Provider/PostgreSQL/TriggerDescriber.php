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

final class TriggerDescriber implements IDescriber
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
        $actualTriggers = [];

        $schemaFilter = $this->sqlHelper->buildSchemaFilterCondition('n.nspname');
        $extensionFilter = $this->sqlHelper->buildExtensionObjectFilterCondition(
            't.oid',
            'pg_trigger',
        );

        $sql = "
            SELECT
              concat_ws('.', n.nspname, c.relname, t.tgname) as tg_path,
              pg_catalog.pg_get_triggerdef(t.OID, true) AS tg_def,
              t.tgenabled
            FROM pg_catalog.pg_trigger t
            INNER JOIN pg_catalog.pg_class c
              ON c.OID = t.tgrelid
            INNER JOIN pg_catalog.pg_namespace n
              ON n.OID = c.relnamespace
            WHERE
                t.tgisinternal = FALSE
                AND " . $schemaFilter . '
                AND ' . $extensionFilter . '
            ORDER BY tg_path
        ';

        $stmt = $this->conn->query($sql);

        /** @phpstan-ignore-next-line */
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $trigger = (string) $row['tg_path'];
            $definition = (string) $row['tg_def'];
            $definition .= self::triggerStateLabel((string) $row['tgenabled']);

            $actualTriggers[$trigger] = $definition;
        }

        return $actualTriggers;
    }

    public static function triggerStateLabel(string $tgenabled): string
    {
        switch ($tgenabled) {
            case 'D':
                return ' [disabled]';
            case 'R':
                return ' [replica]';
            case 'A':
                return ' [always]';
            default:
                return '';
        }
    }
}
