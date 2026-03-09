<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Filesystem;

use SchemaKeeper\Dto\Section;
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Filesystem\{DumpReader, FilesystemHelper, SectionReader};
use SchemaKeeper\Tests\Unit\UnitTestCase;

class DumpReaderTest extends UnitTestCase
{
    private FilesystemHelper $helper;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = new FilesystemHelper();
        $this->tmpDir = sys_get_temp_dir() . '/schema_keeper_reader_' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->helper->rmDirIfExisted($this->tmpDir);

        parent::tearDown();
    }

    public function testReadOk(): void
    {
        mkdir($this->tmpDir . '/structure/public/tables', 0755, true);
        mkdir($this->tmpDir . '/structure/auth/tables', 0755, true);
        mkdir($this->tmpDir . '/extensions', 0755, true);

        file_put_contents($this->tmpDir . '/extensions/plpgsql.txt', 'pg_catalog');
        file_put_contents($this->tmpDir . '/structure/public/tables/users.txt', 'users_def');
        file_put_contents($this->tmpDir . '/structure/auth/tables/roles.txt', 'roles_def');
        file_put_contents($this->tmpDir . '/structure/.gitkeep', '');

        $reader = new DumpReader(new SectionReader($this->helper), $this->helper);
        $dump = $reader->read($this->tmpDir);

        self::assertEquals(['plpgsql' => 'pg_catalog'], $dump->getExtensions());
        self::assertCount(2, $dump->getSchemas());

        $schemaNames = array_map(static fn ($s) => $s->getSchemaName(), $dump->getSchemas());
        sort($schemaNames);
        self::assertEquals(['auth', 'public'], $schemaNames);

        foreach ($dump->getSchemas() as $schema) {
            if ($schema->getSchemaName() === 'public') {
                self::assertEquals(['users' => 'users_def'], $schema->getSection(Section::TABLES));
            }

            if ($schema->getSchemaName() === 'auth') {
                self::assertEquals(['roles' => 'roles_def'], $schema->getSection(Section::TABLES));
            }
        }
    }

    public function testReadSkipsSections(): void
    {
        mkdir($this->tmpDir . '/structure/public/tables', 0755, true);
        mkdir($this->tmpDir . '/structure/public/triggers', 0755, true);
        mkdir($this->tmpDir . '/extensions', 0755, true);

        file_put_contents($this->tmpDir . '/structure/public/tables/users.txt', 'users_def');
        file_put_contents($this->tmpDir . '/structure/public/triggers/t.on_insert.sql', 'trigger_def');

        $reader = new DumpReader(
            new SectionReader($this->helper),
            $this->helper,
            [Section::TRIGGERS],
        );
        $dump = $reader->read($this->tmpDir);

        $schema = $dump->getSchemas()[0];
        self::assertEquals(['users' => 'users_def'], $schema->getSection(Section::TABLES));
        self::assertEquals([], $schema->getSection(Section::TRIGGERS));
    }

    public function testEmptyDumpThrowsException(): void
    {
        mkdir($this->tmpDir . '/structure', 0755, true);
        mkdir($this->tmpDir . '/extensions', 0755, true);

        $reader = new DumpReader(new SectionReader($this->helper), $this->helper);

        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Dump is empty');

        $reader->read($this->tmpDir);
    }
}
