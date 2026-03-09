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

final class ExtensionDescriber implements IDescriber
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
        $actualExtensions = [];

        $sql = '
            SELECT
              ext.extname,
              nsp.nspname
            FROM pg_catalog.pg_extension ext
              LEFT JOIN pg_catalog.pg_namespace nsp
                ON nsp.OID = ext.extnamespace
            WHERE ' . $this->sqlHelper->buildExtensionNameFilterCondition('extname') . '
            ORDER BY extname;
        ';

        $stmt = $this->conn->query($sql);

        /** @phpstan-ignore-next-line */
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $extension = (string) $row['extname'];
            $schema = (string) $row['nspname'];
            $actualExtensions[$extension] = $schema;
        }

        return $actualExtensions;
    }
}
