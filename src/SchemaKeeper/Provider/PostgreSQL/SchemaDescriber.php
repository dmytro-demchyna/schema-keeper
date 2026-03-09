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

final class SchemaDescriber implements IDescriber
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
        $actualSchemas = [];

        $sql = '
            SELECT nspname AS schema_name
            FROM pg_catalog.pg_namespace
            WHERE ' . $this->sqlHelper->buildSchemaFilterCondition('nspname') . '
            ORDER BY nspname
        ';

        $stmt = $this->conn->query($sql);

        /** @phpstan-ignore-next-line */
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $schema = (string) $row['schema_name'];
            $actualSchemas[$schema] = $schema;
        }

        return $actualSchemas;
    }
}
