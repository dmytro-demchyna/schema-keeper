<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Integration\SchemaKeeper\Provider\PostgreSQL;

use SchemaKeeper\Dto\Section;
use SchemaKeeper\Provider\{PostgreSQL\PostgreSqlProvider, ProviderFactory};
use SchemaKeeper\Tests\Integration\PostgreSqlTestCase;

abstract class AbstractPostgreSqlProviderTest extends PostgreSqlTestCase
{
    private PostgreSqlProvider $target;

    protected function setUp(): void
    {
        parent::setUp();

        $conn = self::getConn();
        $params = self::getDbParams();
        $providerFactory = new ProviderFactory();
        $this->target = $providerFactory->createProvider($conn, $params);
    }

    final public function testSchemas(): void
    {
        self::assertEquals($this->expectedSchemas(), $this->target->getSchemas());
    }

    final public function testExtensions(): void
    {
        self::assertEquals($this->expectedExtensions(), $this->target->getExtensions());
    }

    final public function testTables(): void
    {
        self::assertEquals($this->expectedTables(), $this->target->getData(Section::TABLES));
    }

    final public function testViews(): void
    {
        self::assertEquals($this->expectedViews(), $this->target->getData(Section::VIEWS));
    }

    final public function testMaterializedViews(): void
    {
        self::assertEquals($this->expectedMaterializedViews(), $this->target->getData(Section::MATERIALIZED_VIEWS));
    }

    final public function testFunctions(): void
    {
        self::assertEquals($this->expectedFunctions(), $this->target->getData(Section::FUNCTIONS));
    }

    final public function testProcedures(): void
    {
        self::assertEquals($this->expectedProcedures(), $this->target->getData(Section::PROCEDURES));
    }

    final public function testTriggers(): void
    {
        self::assertEquals($this->expectedTriggers(), $this->target->getData(Section::TRIGGERS));
    }

    final public function testTypes(): void
    {
        self::assertEquals($this->expectedTypes(), $this->target->getData(Section::TYPES));
    }

    final public function testSequences(): void
    {
        self::assertEquals($this->expectedSequences(), $this->target->getData(Section::SEQUENCES));
    }

    protected function expectedSchemas(): array
    {
        return [
            'public' => 'public',
            'test_schema' => 'test_schema',
            'test~tilde' => 'test~tilde',
        ];
    }

    protected function expectedExtensions(): array
    {
        return [
            'btree_gist' => 'extensions',
            'pgcrypto' => 'extensions',
        ];
    }

    protected function expectedSequences(): array
    {
        return [
            'public.seq_bigint_custom' => "Sequence \"public.seq_bigint_custom\"\nData type: bigint\nStart value: 1000000\nMin value: 1000000\nMax value: 9999999999\nIncrement: 100\nCycle: NO\nCache size: 1\n",
            'public.seq_custom_range' => "Sequence \"public.seq_custom_range\"\nData type: integer\nStart value: 500\nMin value: 100\nMax value: 900\nIncrement: 5\nCycle: NO\nCache size: 1\n",
            'public.seq_descending' => "Sequence \"public.seq_descending\"\nData type: integer\nStart value: 1000\nMin value: 1\nMax value: 1000\nIncrement: -1\nCycle: NO\nCache size: 1\n",
            'public.seq_integer' => "Sequence \"public.seq_integer\"\nData type: integer\nStart value: 100\nMin value: 1\nMax value: 1000000\nIncrement: 10\nCycle: NO\nCache size: 1\n",
            'public.seq_smallint' => "Sequence \"public.seq_smallint\"\nData type: smallint\nStart value: 1\nMin value: 1\nMax value: 32767\nIncrement: 1\nCycle: NO\nCache size: 1\n",
            'public.seq_with_cycle' => "Sequence \"public.seq_with_cycle\"\nData type: integer\nStart value: 1\nMin value: 1\nMax value: 100\nIncrement: 1\nCycle: YES\nCache size: 1\n",
            'public.test_child_id_seq' => "Sequence \"public.test_child_id_seq\"\nData type: bigint\nStart value: 1\nMin value: 1\nMax value: 9223372036854775807\nIncrement: 1\nCycle: NO\nCache size: 1\n",
            'public.test_deferrable_id_seq' => "Sequence \"public.test_deferrable_id_seq\"\nData type: bigint\nStart value: 1\nMin value: 1\nMax value: 9223372036854775807\nIncrement: 1\nCycle: NO\nCache size: 1\n",
            'public.test_exclude_id_seq' => "Sequence \"public.test_exclude_id_seq\"\nData type: bigint\nStart value: 1\nMin value: 1\nMax value: 9223372036854775807\nIncrement: 1\nCycle: NO\nCache size: 1\n",
            'public.test_identity_id_seq' => "Sequence \"public.test_identity_id_seq\"\nData type: bigint\nStart value: 1\nMin value: 1\nMax value: 9223372036854775807\nIncrement: 1\nCycle: NO\nCache size: 1\n",
            'public.test_table_id_seq' => "Sequence \"public.test_table_id_seq\"\nData type: bigint\nStart value: 1\nMin value: 1\nMax value: 9223372036854775807\nIncrement: 1\nCycle: NO\nCache size: 1\n",
            'test_schema.schema_seq' => "Sequence \"test_schema.schema_seq\"\nData type: integer\nStart value: 1\nMin value: 1\nMax value: 2147483647\nIncrement: 1\nCycle: NO\nCache size: 1\n",
            'public.seq{curly}' => "Sequence \"public.seq{curly}\"\nData type: integer\nStart value: 1\nMin value: 1\nMax value: 100\nIncrement: 1\nCycle: NO\nCache size: 1\n",
        ];
    }

    abstract protected function expectedTables(): array;

    abstract protected function expectedViews(): array;

    abstract protected function expectedMaterializedViews(): array;

    abstract protected function expectedFunctions(): array;

    abstract protected function expectedProcedures(): array;

    abstract protected function expectedTriggers(): array;

    abstract protected function expectedTypes(): array;
}
