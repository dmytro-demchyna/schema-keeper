<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Provider\PostgreSQL;

use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PDO;
use SchemaKeeper\Provider\PostgreSQL\SqlHelper;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class SqlHelperTest extends UnitTestCase
{
    /** @var PDO&MockInterface */
    private PDO $conn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conn = Mockery::mock(PDO::class);
    }

    public function testBuildExtensionNameFilterConditionEmpty(): void
    {
        $target = new SqlHelper($this->conn, [], [], true);

        self::assertEquals('TRUE', $target->buildExtensionNameFilterCondition('extname'));
    }

    public function testBuildExtensionNameFilterConditionSingleName(): void
    {
        $this->conn->shouldReceive('quote')->with('pg_catalog')->andReturn("'pg_catalog'")->once();

        $target = new SqlHelper($this->conn, [], ['pg_catalog'], true);

        self::assertEquals(
            "extname NOT IN ('pg_catalog')",
            $target->buildExtensionNameFilterCondition('extname'),
        );
    }

    public function testBuildExtensionNameFilterConditionMultipleNames(): void
    {
        $this->conn->shouldReceive('quote')->with('pg_catalog')->andReturn("'pg_catalog'")->once();
        $this->conn->shouldReceive('quote')
            ->with('information_schema')
            ->andReturn("'information_schema'")->once()
        ;

        $target = new SqlHelper($this->conn, [], ['pg_catalog', 'information_schema'], true);

        self::assertEquals(
            "extname NOT IN ('pg_catalog', 'information_schema')",
            $target->buildExtensionNameFilterCondition('extname'),
        );
    }

    public function testBuildSchemaFilterConditionNoFilters(): void
    {
        $target = new SqlHelper($this->conn, [], [], false);

        self::assertEquals('TRUE', $target->buildSchemaFilterCondition('n.nspname'));
    }

    public function testBuildSchemaFilterConditionTemporaryOnly(): void
    {
        $target = new SqlHelper($this->conn, [], [], true);

        self::assertEquals(
            "n.nspname NOT LIKE 'pg_temp_%' AND n.nspname NOT LIKE 'pg_toast_temp_%'",
            $target->buildSchemaFilterCondition('n.nspname'),
        );
    }

    public function testBuildSchemaFilterConditionNamesAndTemporary(): void
    {
        $this->conn->shouldReceive('quote')->with('pg_catalog')->andReturn("'pg_catalog'")->once();

        $target = new SqlHelper($this->conn, ['pg_catalog'], [], true);

        self::assertEquals(
            "n.nspname NOT IN ('pg_catalog')"
            . " AND n.nspname NOT LIKE 'pg_temp_%'"
            . " AND n.nspname NOT LIKE 'pg_toast_temp_%'",
            $target->buildSchemaFilterCondition('n.nspname'),
        );
    }

    public function testBuildExtensionObjectFilterConditionEmpty(): void
    {
        $target = new SqlHelper($this->conn, [], [], true);

        self::assertEquals('TRUE', $target->buildExtensionObjectFilterCondition('p.oid', 'pg_proc'));
    }

    public function testBuildExtensionObjectFilterConditionSingleName(): void
    {
        $this->conn->shouldReceive('quote')->with('pgcrypto')->andReturn("'pgcrypto'")->once();

        $target = new SqlHelper($this->conn, [], ['pgcrypto'], true);

        $expected = 'p.oid NOT IN ('
            . ' SELECT d.objid FROM pg_catalog.pg_depend d'
            . ' JOIN pg_catalog.pg_extension e ON e.oid = d.refobjid'
            . " WHERE d.refclassid = 'pg_extension'::regclass"
            . " AND d.classid = 'pg_proc'::regclass"
            . " AND d.deptype = 'e'"
            . " AND e.extname IN ('pgcrypto'))";

        self::assertEquals(
            $expected,
            $target->buildExtensionObjectFilterCondition('p.oid', 'pg_proc'),
        );
    }

    public function testBuildExtensionObjectFilterConditionMultipleNames(): void
    {
        $this->conn->shouldReceive('quote')->with('pgcrypto')->andReturn("'pgcrypto'")->once();
        $this->conn->shouldReceive('quote')->with('btree_gist')->andReturn("'btree_gist'")->once();

        $target = new SqlHelper($this->conn, [], ['pgcrypto', 'btree_gist'], true);

        $expected = 'p.oid NOT IN ('
            . ' SELECT d.objid FROM pg_catalog.pg_depend d'
            . ' JOIN pg_catalog.pg_extension e ON e.oid = d.refobjid'
            . " WHERE d.refclassid = 'pg_extension'::regclass"
            . " AND d.classid = 'pg_proc'::regclass"
            . " AND d.deptype = 'e'"
            . " AND e.extname IN ('pgcrypto', 'btree_gist'))";

        self::assertEquals(
            $expected,
            $target->buildExtensionObjectFilterCondition('p.oid', 'pg_proc'),
        );
    }

    public function testBuildExtensionObjectFilterConditionInvalidCatalogClass(): void
    {
        $target = new SqlHelper($this->conn, [], ['pgcrypto'], true);

        $this->expectException(InvalidArgumentException::class);

        $target->buildExtensionObjectFilterCondition('p.oid', 'invalid_table');
    }
}
