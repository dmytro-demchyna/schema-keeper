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

class PostgreSqlProviderSectionFilterTest extends PostgreSqlTestCase
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
            new Parameters([], [], [Section::TRIGGERS]),
        );
    }

    public function testTriggersRemovedFromTableDescription(): void
    {
        $tables = $this->withoutFilter->getData(Section::TABLES);
        self::assertStringContainsString('Triggers:', $tables['public.test_table']);

        $tables = $this->withFilter->getData(Section::TABLES);
        self::assertStringNotContainsString('Triggers:', $tables['public.test_table']);
    }

    public function testTriggersRemovedFromViewDescription(): void
    {
        $views = $this->withoutFilter->getData(Section::VIEWS);
        self::assertStringContainsString('Triggers:', $views['public.test_view_check']);

        $views = $this->withFilter->getData(Section::VIEWS);
        self::assertStringNotContainsString('Triggers:', $views['public.test_view_check']);
    }
}
