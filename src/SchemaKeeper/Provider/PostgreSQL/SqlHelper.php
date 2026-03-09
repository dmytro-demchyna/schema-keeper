<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Provider\PostgreSQL;

use InvalidArgumentException;
use PDO;

final class SqlHelper
{
    private const ALLOWED_CATALOG_CLASSES = ['pg_class', 'pg_proc', 'pg_type', 'pg_trigger'];

    public const PG_VERSION_10 = 100000;

    public const PG_VERSION_11 = 110000;

    public const PG_VERSION_12 = 120000;

    private PDO $conn;

    /**
     * @var string[]
     */
    private array $skippedSchemas;

    /**
     * @var string[]
     */
    private array $skippedExtensions;

    private bool $skipTemporarySchemas;

    /**
     * @var string[]
     */
    private array $onlySchemas;

    private ?int $serverVersionNum = null;

    public function __construct(
        PDO $conn,
        array $skippedSchemas = [],
        array $skippedExtensions = [],
        bool $skipTemporarySchemas = true,
        array $onlySchemas = [],
        ?int $serverVersionNum = null
    ) {
        $this->conn = $conn;
        $this->skippedSchemas = $skippedSchemas;
        $this->skippedExtensions = $skippedExtensions;
        $this->skipTemporarySchemas = $skipTemporarySchemas;
        $this->onlySchemas = $onlySchemas;
        $this->serverVersionNum = $serverVersionNum;
    }

    public function getServerVersionNum(): int
    {
        if ($this->serverVersionNum === null) {
            $stmt = $this->conn->query('SHOW server_version_num');
            /** @phpstan-ignore-next-line */
            $this->serverVersionNum = (int) $stmt->fetchColumn();
        }

        return $this->serverVersionNum;
    }

    /**
     * @param int[] $oids
     */
    public static function buildOidList(array $oids): string
    {
        return implode(', ', array_map('intval', $oids));
    }

    public function buildSchemaFilterCondition(string $columnName): string
    {
        if ($this->onlySchemas) {
            return $this->buildInCondition($columnName, $this->onlySchemas);
        }

        $conditions = [];
        $excludeNames = $this->buildNotInCondition($columnName, $this->skippedSchemas);

        if ($excludeNames !== 'TRUE') {
            $conditions[] = $excludeNames;
        }

        if ($this->skipTemporarySchemas) {
            $conditions[] = $columnName . " NOT LIKE 'pg_temp_%'";
            $conditions[] = $columnName . " NOT LIKE 'pg_toast_temp_%'";
        }

        if (!$conditions) {
            return 'TRUE';
        }

        return implode(' AND ', $conditions);
    }

    public function buildExtensionObjectFilterCondition(
        string $oidColumn,
        string $catalogClass
    ): string {
        if (!in_array($catalogClass, self::ALLOWED_CATALOG_CLASSES, true)) {
            throw new InvalidArgumentException(
                'Invalid catalog class: ' . $catalogClass
                . '. Allowed: ' . implode(', ', self::ALLOWED_CATALOG_CLASSES),
            );
        }

        if (!$this->skippedExtensions) {
            return 'TRUE';
        }

        $quoted = [];

        foreach ($this->skippedExtensions as $name) {
            $quoted[] = $this->conn->quote($name);
        }

        $inList = implode(', ', $quoted);

        return $oidColumn . ' NOT IN ('
            . ' SELECT d.objid FROM pg_catalog.pg_depend d'
            . ' JOIN pg_catalog.pg_extension e ON e.oid = d.refobjid'
            . " WHERE d.refclassid = 'pg_extension'::regclass"
            . " AND d.classid = '" . $catalogClass . "'::regclass"
            . " AND d.deptype = 'e'"
            . ' AND e.extname IN (' . $inList . ')'
            . ')';
    }

    public function buildExtensionNameFilterCondition(string $columnName): string
    {
        return $this->buildNotInCondition($columnName, $this->skippedExtensions);
    }

    private function buildInCondition(string $columnName, array $names): string
    {
        if (!$names) {
            return 'FALSE';
        }

        $quoted = [];

        foreach ($names as $name) {
            $quoted[] = $this->conn->quote($name);
        }

        return "$columnName IN (" . implode(', ', $quoted) . ')';
    }

    private function buildNotInCondition(string $columnName, array $names): string
    {
        if (!$names) {
            return 'TRUE';
        }

        $quoted = [];

        foreach ($names as $name) {
            $quoted[] = $this->conn->quote($name);
        }

        return "$columnName NOT IN (" . implode(', ', $quoted) . ')';
    }
}
