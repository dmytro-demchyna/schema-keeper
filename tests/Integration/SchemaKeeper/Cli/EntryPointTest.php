<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Integration\SchemaKeeper\Cli;

use SchemaKeeper\Cli\{ArgvParser, CredentialsFactory, DiffBuilder, EntryPoint};
use SchemaKeeper\Filesystem\FilesystemHelper;
use SchemaKeeper\KeeperFactory;
use SchemaKeeper\Tests\Integration\PostgreSqlTestCase;

class EntryPointTest extends PostgreSqlTestCase
{
    private EntryPoint $target;

    /**
     * @var string[]
     */
    private array $baseArgs;

    /**
     * @var string[]
     */
    private array $baseUrlArgs;

    private FilesystemHelper $helper;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new EntryPoint(
            new ArgvParser(new CredentialsFactory()),
            new KeeperFactory(),
            new DiffBuilder(),
        );
        $this->helper = new FilesystemHelper();
        $this->tmpDir = sys_get_temp_dir() . '/sk_entry_' . uniqid();

        $host = self::getDbHost();

        $this->baseArgs = [
            'schemakeeper',
            '-h', $host, '-p', '5432', '-d', 'schema_keeper', '-U', 'postgres', '--password', 'postgres',
        ];

        $this->baseUrlArgs = [
            'schemakeeper',
            '--url', 'postgresql://postgres:postgres@' . $host . ':5432/schema_keeper',
        ];
    }

    protected function tearDown(): void
    {
        $this->helper->rmDirIfExisted($this->tmpDir);

        parent::tearDown();
    }

    public function testOk(): void
    {
        $result = $this->target->run(array_merge($this->baseArgs, ['dump', $this->tmpDir]));
        self::assertEquals('Success: Dump saved ' . $this->tmpDir, $result->getMessage());
        self::assertSame(0, $result->getStatus());

        $result = $this->target->run(array_merge($this->baseArgs, ['verify', $this->tmpDir]));
        self::assertEquals('Success: Dump verified ' . $this->tmpDir, $result->getMessage());
        self::assertSame(0, $result->getStatus());
    }

    public function testOkWithUrl(): void
    {
        $result = $this->target->run(array_merge($this->baseUrlArgs, ['dump', $this->tmpDir]));
        self::assertEquals('Success: Dump saved ' . $this->tmpDir, $result->getMessage());
        self::assertSame(0, $result->getStatus());

        $result = $this->target->run(array_merge($this->baseUrlArgs, ['verify', $this->tmpDir]));
        self::assertEquals('Success: Dump verified ' . $this->tmpDir, $result->getMessage());
        self::assertSame(0, $result->getStatus());
    }

    public function testHelp(): void
    {
        $result = $this->target->run(['schemakeeper', '--help']);
        self::assertEquals(EntryPoint::getVersionText() . PHP_EOL . PHP_EOL . EntryPoint::getUsageText(), $result->getMessage());
        self::assertSame(0, $result->getStatus());
    }

    public function testNoArgs(): void
    {
        $result = $this->target->run(['schemakeeper']);
        self::assertEquals(EntryPoint::getVersionText() . PHP_EOL . PHP_EOL . EntryPoint::getUsageText(), $result->getMessage());
        self::assertSame(0, $result->getStatus());
    }

    public function testVersion(): void
    {
        $result = $this->target->run(['schemakeeper', '--version']);
        self::assertSame(EntryPoint::getVersionText(), $result->getMessage());
        self::assertSame(0, $result->getStatus());
    }

    public function testMissingRequiredOption(): void
    {
        $result = $this->target->run(['schemakeeper', 'dump', $this->tmpDir]);
        self::assertEquals(
            'Failure: No connection parameters specified. Use -h, -p, -d, -U or --url',
            $result->getMessage(),
        );
        self::assertSame(3, $result->getStatus());
    }

    public function testUnrecognizedCommand(): void
    {
        $result = $this->target->run(array_merge($this->baseArgs, ['blabla', $this->tmpDir]));
        self::assertEquals(
            'Failure: Unrecognized command: blabla. Available commands: dump, verify',
            $result->getMessage(),
        );
        self::assertSame(3, $result->getStatus());
    }

    public function testUnrecognizedOption(): void
    {
        $result = $this->target->run([
            'schemakeeper', '--blabla',
            '-h', self::getDbHost(), '-p', '5432', '-d', 'schema_keeper', '-U', 'postgres', '--password', 'postgres',
            'dump', '/tmp/test',
        ]);
        self::assertEquals('Failure: Unrecognized option: blabla', $result->getMessage());
        self::assertSame(3, $result->getStatus());
    }

    public function testConnectionFailure(): void
    {
        $result = $this->target->run([
            'schemakeeper',
            '--url', 'postgresql://postgres:postgres@postgres10:9999/nonexistent',
            'dump', $this->tmpDir,
        ]);

        self::assertSame(2, $result->getStatus());
        self::assertStringStartsWith('Failure: ', $result->getMessage());
    }

    public function testVerifyDiff(): void
    {
        $this->target->run(array_merge($this->baseArgs, ['dump', $this->tmpDir]));
        $this->helper->rmDirIfExisted($this->tmpDir . '/structure/public/triggers');

        $result = $this->target->run(array_merge($this->baseArgs, ['verify', $this->tmpDir]));

        self::assertSame(1, $result->getStatus());
        self::assertStringStartsWith('Failure: Dump and current database are not equal:', $result->getMessage());
        self::assertStringContainsString('+++ triggers/public.test_table.test_trigger', $result->getMessage());
    }
}
