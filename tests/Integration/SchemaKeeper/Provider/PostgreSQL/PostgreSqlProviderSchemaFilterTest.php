<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Integration\SchemaKeeper\Provider\PostgreSQL;

use SchemaKeeper\Dto\{Parameters, Section};
use SchemaKeeper\Provider\{PostgreSQL\PostgreSqlProvider, ProviderFactory};
use SchemaKeeper\Tests\Integration\PostgreSqlTestCase;

class PostgreSqlProviderSchemaFilterTest extends PostgreSqlTestCase
{
    private PostgreSqlProvider $withoutFilter;

    private PostgreSqlProvider $withFilter;

    protected function setUp(): void
    {
        parent::setUp();

        $conn = self::getConn();
        $providerFactory = new ProviderFactory();

        $this->withoutFilter = $providerFactory->createProvider(
            $conn,
            new Parameters(),
        );

        $this->withFilter = $providerFactory->createProvider(
            $conn,
            new Parameters(['test_schema']),
        );
    }

    public function testReferencedByFilteredBySkippedSchema(): void
    {
        $tablesWithout = $this->withoutFilter->getData(Section::TABLES);
        $tableWith = $this->withFilter->getData(Section::TABLES);

        $withoutOutput = $tablesWithout['public.test_table'];
        $withOutput = $tableWith['public.test_table'];

        self::assertStringContainsString('test_schema', $withoutOutput);
        self::assertStringNotContainsString('test_schema', $withOutput);
    }

    public function testPartitionsFilteredBySkippedSchema(): void
    {
        $tablesWithout = $this->withoutFilter->getData(Section::TABLES);
        $tableWith = $this->withFilter->getData(Section::TABLES);

        $withoutOutput = $tablesWithout['public.test_partitioned'];
        $withOutput = $tableWith['public.test_partitioned'];

        self::assertStringContainsString('test_schema.test_part_2026', $withoutOutput);
        self::assertStringNotContainsString('test_schema.test_part_2026', $withOutput);
    }
}
