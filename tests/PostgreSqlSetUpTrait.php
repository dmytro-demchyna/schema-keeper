<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests;

use PDO;
use SchemaKeeper\Provider\PostgreSQL\SqlHelper;

trait PostgreSqlSetUpTrait
{
    /**
     * @var PDO[]
     */
    private static array $connections = [];

    protected function setUp(): void
    {
        parent::setUp();

        static::loadFixture();
    }

    protected static function getDbHost(): string
    {
        return 'postgres10';
    }

    protected static function getConn(): PDO
    {
        $host = static::getDbHost();

        if (!isset(self::$connections[$host])) {
            $dsn = 'pgsql:host=' . $host . ';port=5432;dbname=schema_keeper;user=postgres;password=postgres';

            self::$connections[$host] = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }

        return self::$connections[$host];
    }

    protected static function getServerVersionNum(): int
    {
        $stmt = static::getConn()->query('SHOW server_version_num');

        return (int) $stmt->fetchColumn();
    }

    private static function loadFixture(): void
    {
        $conn = static::getConn();
        $fixtureDir = __DIR__ . '/fixtures';

        $sql = file_get_contents($fixtureDir . '/structure.sql');
        $conn->exec($sql);

        if (static::getServerVersionNum() >= SqlHelper::PG_VERSION_11) {
            $sql = file_get_contents($fixtureDir . '/structure_pg11.sql');
            $conn->exec($sql);
        }
    }
}
