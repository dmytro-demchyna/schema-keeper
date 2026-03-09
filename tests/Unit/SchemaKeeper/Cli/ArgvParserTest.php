<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Cli;

use SchemaKeeper\Cli\{ArgvParser, CredentialsFactory};
use SchemaKeeper\Dto\{Parameters, Section};
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class ArgvParserTest extends UnitTestCase
{
    private ArgvParser $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new ArgvParser(new CredentialsFactory());
    }

    public function testShortFlags(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres', 'dump', '/tmp/dump',
        ]);

        self::assertInstanceOf(Parameters::class, $parsed->getParams());
        self::assertEquals('pgsql:host=localhost;port=5432;dbname=mydb', $parsed->getCredentials()->getDsn());
        self::assertEquals('postgres', $parsed->getCredentials()->getUser());
        self::assertNull($parsed->getCredentials()->getPassword());
        self::assertEquals('/tmp/dump', $parsed->getPath());
        self::assertEquals('dump', $parsed->getCommand());
        self::assertEquals(Parameters::DEFAULT_SKIPPED_SCHEMAS, $parsed->getParams()->getSkippedSchemas());
        self::assertEquals(Parameters::DEFAULT_SKIPPED_EXTENSIONS, $parsed->getParams()->getSkippedExtensions());
        self::assertEquals([], $parsed->getParams()->getSkippedSections());
        self::assertTrue($parsed->getParams()->shouldSkipTemporarySchemas());
    }

    public function testLongFlags(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper',
            '--host', 'db.example.com', '--port', '5433', '--dbname', 'testdb', '--username', 'admin',
            '--password', 'secret',
            'verify', '/tmp/dump',
        ]);

        self::assertEquals('pgsql:host=db.example.com;port=5433;dbname=testdb', $parsed->getCredentials()->getDsn());
        self::assertEquals('admin', $parsed->getCredentials()->getUser());
        self::assertEquals('secret', $parsed->getCredentials()->getPassword());
        self::assertEquals('/tmp/dump', $parsed->getPath());
        self::assertEquals('verify', $parsed->getCommand());
    }

    public function testWithAllOptions(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper',
            '-h', 'db.example.com', '-p', '5433', '-d', 'testdb', '-U', 'admin', '--password', 'secret',
            '--skip-schema', 'my_custom',
            '--skip-extension', 'my_ext',
            '--skip-section', 'triggers', '--skip-section', 'sequences',
            'verify', '/tmp/dump',
        ]);

        self::assertEquals('pgsql:host=db.example.com;port=5433;dbname=testdb', $parsed->getCredentials()->getDsn());
        self::assertEquals('admin', $parsed->getCredentials()->getUser());
        self::assertEquals('secret', $parsed->getCredentials()->getPassword());
        $expectedSchemas = array_merge(Parameters::DEFAULT_SKIPPED_SCHEMAS, ['my_custom']);
        self::assertEquals($expectedSchemas, $parsed->getParams()->getSkippedSchemas());
        $expectedExtensions = array_merge(Parameters::DEFAULT_SKIPPED_EXTENSIONS, ['my_ext']);
        self::assertEquals($expectedExtensions, $parsed->getParams()->getSkippedExtensions());
        self::assertEquals(['triggers', 'sequences'], $parsed->getParams()->getSkippedSections());
        self::assertTrue($parsed->getParams()->shouldSkipTemporarySchemas());
        self::assertEquals('/tmp/dump', $parsed->getPath());
        self::assertEquals('verify', $parsed->getCommand());
    }

    public function testUrl(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '--url', 'postgresql://postgres:secret@localhost:5432/mydb', 'dump', '/tmp/dump',
        ]);

        self::assertEquals('pgsql:host=localhost;port=5432;dbname=mydb', $parsed->getCredentials()->getDsn());
        self::assertEquals('postgres', $parsed->getCredentials()->getUser());
        self::assertEquals('secret', $parsed->getCredentials()->getPassword());
        self::assertEquals('dump', $parsed->getCommand());
        self::assertEquals('/tmp/dump', $parsed->getPath());
    }

    public function testUrlPostgresScheme(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '--url', 'postgres://admin:pass@db.example.com:5433/testdb', 'dump', '/tmp/dump',
        ]);

        self::assertEquals('pgsql:host=db.example.com;port=5433;dbname=testdb', $parsed->getCredentials()->getDsn());
        self::assertEquals('admin', $parsed->getCredentials()->getUser());
        self::assertEquals('pass', $parsed->getCredentials()->getPassword());
    }

    public function testUnrecognizedShortFlag(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Unrecognized option: -x');

        $this->target->parse([
            'schemakeeper', '-x', 'value', 'dump', '/tmp/dump',
        ]);
    }

    public function testShortFlagWithoutValue(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Option -d requires a value');

        $this->target->parse(['schemakeeper', '-d']);
    }

    public function testMissingCommand(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Command not specified');

        $this->target->parse(['schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres']);
    }

    public function testMissingDumpDir(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Dump directory not specified');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres', 'dump',
        ]);
    }

    public function testSkipSchemaWithoutValue(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Option --skip-schema requires a value');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres', '--skip-schema',
        ]);
    }

    public function testSkipExtensionWithoutValue(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Option --skip-extension requires a value');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres', '--skip-extension',
        ]);
    }

    public function testSkipSectionWithoutValue(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Option --skip-section requires a value');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres', '--skip-section',
        ]);
    }

    public function testSkipSectionInvalidValue(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Invalid --skip-section value: "indexes"');

        $this->target->parse([
            'schemakeeper',
            '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--skip-section', 'indexes',
            'dump', '/tmp/dump',
        ]);
    }

    public function testUnrecognizedOption(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Unrecognized option: blabla');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--blabla', 'dump', '/tmp',
        ]);
    }

    public function testUnrecognizedOptionWithEqualsFormat(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Unrecognized option: unknown');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--unknown=value', 'dump', '/tmp',
        ]);
    }

    public function testDoubleDashStopsOptionParsing(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--', '--unknown', '/tmp',
        ]);

        self::assertEquals('--unknown', $parsed->getCommand());
        self::assertEquals('/tmp', $parsed->getPath());
    }

    public function testSkipSchemaMergesWithDefaults(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--skip-schema', 'my_schema',
            'dump', '/tmp/dump',
        ]);

        $expected = array_merge(Parameters::DEFAULT_SKIPPED_SCHEMAS, ['my_schema']);
        self::assertEquals($expected, $parsed->getParams()->getSkippedSchemas());
        self::assertEquals(Parameters::DEFAULT_SKIPPED_EXTENSIONS, $parsed->getParams()->getSkippedExtensions());
    }

    public function testNoDefaultSkipWithValue(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Option --no-default-skip does not accept a value');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--no-default-skip=foo',
            'dump', '/tmp/dump',
        ]);
    }

    public function testNoDefaultSkip(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--no-default-skip',
            '--skip-schema', 'custom_schema',
            '--skip-extension', 'custom_ext',
            'dump', '/tmp/dump',
        ]);

        self::assertEquals(['custom_schema'], $parsed->getParams()->getSkippedSchemas());
        self::assertEquals(['custom_ext'], $parsed->getParams()->getSkippedExtensions());
        self::assertFalse($parsed->getParams()->shouldSkipTemporarySchemas());
    }

    public function testHelpWithValue(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Option --help does not accept a value');

        $this->target->parse([
            'schemakeeper', '--help=foo',
        ]);
    }

    public function testVersionWithValue(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Option --version does not accept a value');

        $this->target->parse([
            'schemakeeper', '--version=bar',
        ]);
    }

    public function testNoDefaultSkipWithoutExplicitSkips(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--no-default-skip',
            'dump', '/tmp/dump',
        ]);

        self::assertEquals([], $parsed->getParams()->getSkippedSchemas());
        self::assertEquals([], $parsed->getParams()->getSkippedExtensions());
        self::assertFalse($parsed->getParams()->shouldSkipTemporarySchemas());
    }

    public function testOnlySchema(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--only-schema', 'public',
            'dump', '/tmp/dump',
        ]);

        self::assertEquals(['public'], $parsed->getParams()->getOnlySchemas());
        self::assertEquals([], $parsed->getParams()->getSkippedSchemas());
    }

    public function testOnlySchemaMultiple(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--only-schema', 'public', '--only-schema', 'billing',
            'dump', '/tmp/dump',
        ]);

        self::assertEquals(['public', 'billing'], $parsed->getParams()->getOnlySchemas());
    }

    public function testOnlySection(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--only-section', 'tables',
            'dump', '/tmp/dump',
        ]);

        self::assertEquals(Section::allExcept(['tables']), $parsed->getParams()->getSkippedSections());
    }

    public function testOnlySectionMultiple(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--only-section', 'tables', '--only-section', 'functions',
            'dump', '/tmp/dump',
        ]);

        self::assertEquals(Section::allExcept(['tables', 'functions']), $parsed->getParams()->getSkippedSections());
    }

    public function testOnlySectionInvalidValue(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Invalid --only-section value: "indexes"');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--only-section', 'indexes',
            'dump', '/tmp/dump',
        ]);
    }

    public function testOnlySchemaWithSkipSchemaError(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Options --only-schema and --skip-schema are mutually exclusive');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--only-schema', 'public', '--skip-schema', 'billing',
            'dump', '/tmp/dump',
        ]);
    }

    public function testOnlySectionWithSkipSectionError(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Options --only-section and --skip-section are mutually exclusive');

        $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--only-section', 'tables', '--skip-section', 'views',
            'dump', '/tmp/dump',
        ]);
    }

    public function testOnlySchemaWithSkipSection(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--only-schema', 'public', '--skip-section', 'triggers',
            'dump', '/tmp/dump',
        ]);

        self::assertEquals(['public'], $parsed->getParams()->getOnlySchemas());
        self::assertEquals(['triggers'], $parsed->getParams()->getSkippedSections());
    }

    public function testOnlySectionWithSkipSchema(): void
    {
        $parsed = $this->target->parse([
            'schemakeeper', '-h', 'localhost', '-p', '5432', '-d', 'mydb', '-U', 'postgres',
            '--only-section', 'tables', '--skip-schema', 'test_schema',
            'dump', '/tmp/dump',
        ]);

        $expectedSchemas = array_merge(Parameters::DEFAULT_SKIPPED_SCHEMAS, ['test_schema']);
        self::assertEquals($expectedSchemas, $parsed->getParams()->getSkippedSchemas());
        self::assertEquals(Section::allExcept(['tables']), $parsed->getParams()->getSkippedSections());
        self::assertEquals([], $parsed->getParams()->getOnlySchemas());
    }
}
