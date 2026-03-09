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

final class ProcedureDescriber implements IDescriber
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
        if ($this->sqlHelper->getServerVersionNum() < SqlHelper::PG_VERSION_11) {
            return [];
        }

        $actualProcedures = [];

        $schemaFilter = $this->sqlHelper->buildSchemaFilterCondition('n.nspname');
        $extensionFilter = $this->sqlHelper->buildExtensionObjectFilterCondition(
            'p.oid',
            'pg_proc',
        );

        $sql = '
            SELECT
              concat_ws(\'.\', n.nspname, p.proname) AS pro_path,
              pg_catalog.oidvectortypes(p.proargtypes) AS arg_types,
              pg_catalog.pg_get_functiondef(p.oid) AS pro_def
            FROM pg_catalog.pg_namespace n
              JOIN pg_catalog.pg_proc p
                ON p.pronamespace = n.oid
            WHERE ' . $schemaFilter . '
              AND p.prokind = \'p\'
              AND ' . $extensionFilter . '
            ORDER BY pro_path
        ';

        $stmt = $this->conn->query($sql);

        /** @phpstan-ignore-next-line */
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $proPath = (string) $row['pro_path'];
            $procedure = $proPath . '(' . (string) $row['arg_types'] . ')';
            $actualProcedures[$procedure] = (string) $row['pro_def'];
        }

        return $actualProcedures;
    }
}
