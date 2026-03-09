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

class PostgreSqlProviderExtensionFilterTest extends PostgreSqlTestCase
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
            new Parameters([], ['pgcrypto', 'btree_gist', 'plpgsql']),
        );
    }

    public function testExtensionObjectsRemovedByFilter(): void
    {
        $functions = $this->withoutFilter->getData(Section::FUNCTIONS);
        self::assertArrayHasKey('extensions.gen_random_uuid()', $functions);

        $functions = $this->withFilter->getData(Section::FUNCTIONS);
        self::assertArrayNotHasKey('extensions.gen_random_uuid()', $functions);

        $types = $this->withoutFilter->getData(Section::TYPES);
        self::assertArrayHasKey('extensions.gbtreekey4', $types);

        $types = $this->withFilter->getData(Section::TYPES);
        self::assertArrayNotHasKey('extensions.gbtreekey4', $types);
    }
}
