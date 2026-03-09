<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Cli;

use SchemaKeeper\Cli\CredentialsFactory;
use SchemaKeeper\Exception\KeeperException;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class CredentialsFactoryTest extends UnitTestCase
{
    private CredentialsFactory $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new CredentialsFactory();
    }

    public function testBuildFromParamsWithPassword(): void
    {
        $result = $this->target->createFromParams('localhost', '5432', 'mydb', 'postgres', 'secret');

        self::assertEquals('pgsql:host=localhost;port=5432;dbname=mydb', $result->getDsn());
        self::assertEquals('postgres', $result->getUser());
        self::assertEquals('secret', $result->getPassword());
    }

    public function testBuildFromParamsWithoutPassword(): void
    {
        $result = $this->target->createFromParams('localhost', '5432', 'mydb', 'postgres', null);

        self::assertEquals('pgsql:host=localhost;port=5432;dbname=mydb', $result->getDsn());
        self::assertEquals('postgres', $result->getUser());
        self::assertNull($result->getPassword());
    }

    public function testBuildFromParamsPasswordWithSemicolon(): void
    {
        $result = $this->target->createFromParams('localhost', '5432', 'mydb', 'postgres', 'foo;host=evil');

        self::assertEquals('pgsql:host=localhost;port=5432;dbname=mydb', $result->getDsn());
        self::assertEquals('postgres', $result->getUser());
        self::assertEquals('foo;host=evil', $result->getPassword());
    }

    public function testBuildFromUrlWithPassword(): void
    {
        $result = $this->target->createFromUrl('postgresql://postgres:secret@localhost:5432/mydb');

        self::assertEquals('pgsql:host=localhost;port=5432;dbname=mydb', $result->getDsn());
        self::assertEquals('postgres', $result->getUser());
        self::assertEquals('secret', $result->getPassword());
    }

    public function testBuildFromUrlWithoutPassword(): void
    {
        $result = $this->target->createFromUrl('postgresql://postgres@localhost:5432/mydb');

        self::assertEquals('pgsql:host=localhost;port=5432;dbname=mydb', $result->getDsn());
        self::assertEquals('postgres', $result->getUser());
        self::assertNull($result->getPassword());
    }

    public function testBuildFromUrlPostgresScheme(): void
    {
        $result = $this->target->createFromUrl('postgres://admin:pass@db.example.com:5433/testdb');

        self::assertEquals('pgsql:host=db.example.com;port=5433;dbname=testdb', $result->getDsn());
        self::assertEquals('admin', $result->getUser());
        self::assertEquals('pass', $result->getPassword());
    }

    public function testBuildFromUrlDecodesPassword(): void
    {
        $result = $this->target->createFromUrl('postgresql://user:p%40ss%3Dw0rd@localhost:5432/mydb');

        self::assertEquals('pgsql:host=localhost;port=5432;dbname=mydb', $result->getDsn());
        self::assertEquals('user', $result->getUser());
        self::assertEquals('p@ss=w0rd', $result->getPassword());
    }

    public function testBuildFromUrlDecodesDbname(): void
    {
        $result = $this->target->createFromUrl('postgresql://user:pass@localhost:5432/my%20db');

        self::assertEquals('pgsql:host=localhost;port=5432;dbname=my db', $result->getDsn());
        self::assertEquals('user', $result->getUser());
        self::assertEquals('pass', $result->getPassword());
    }

    public function testBuildFromUrlInvalidScheme(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Invalid URL scheme: "mysql". Expected "postgresql" or "postgres"');

        $this->target->createFromUrl('mysql://user:pass@localhost:3306/mydb');
    }

    public function testBuildFromUrlInvalidUrl(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Invalid connection URL');

        $this->target->createFromUrl('postgresql:///mydb');
    }

    public function testBuildFromUrlMissingPort(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Connection URL is missing port');

        $this->target->createFromUrl('postgresql://postgres@localhost/mydb');
    }

    public function testBuildFromUrlMissingDbname(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Connection URL is missing database name');

        $this->target->createFromUrl('postgresql://postgres@localhost:5432');
    }

    public function testBuildFromUrlMissingUser(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Connection URL is missing username');

        $this->target->createFromUrl('postgresql://localhost:5432/mydb');
    }

    public function testBuildFromOptionsMutualExclusivity(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Cannot combine --url with individual connection options');

        $this->target->createFromOptions([
            'url' => ['postgresql://postgres:secret@localhost:5432/mydb'],
            'host' => ['localhost'],
        ]);
    }

    public function testBuildFromOptionsDuplicateOption(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Option --host specified more than once');

        $this->target->createFromOptions([
            'host' => ['host1', 'host2'],
            'port' => ['5432'],
            'dbname' => ['mydb'],
            'username' => ['postgres'],
        ]);
    }

    public function testBuildFromOptionsNoConnectionParams(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('No connection parameters specified. Use -h, -p, -d, -U or --url');

        $this->target->createFromOptions([]);
    }

    public function testBuildFromOptionsMissingHost(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Required option -h (--host) not specified');

        $this->target->createFromOptions([
            'port' => ['5432'],
            'dbname' => ['mydb'],
            'username' => ['postgres'],
        ]);
    }

    public function testBuildFromOptionsMissingPort(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Required option -p (--port) not specified');

        $this->target->createFromOptions([
            'host' => ['localhost'],
            'dbname' => ['mydb'],
            'username' => ['postgres'],
        ]);
    }

    public function testBuildFromOptionsMissingDbname(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Required option -d (--dbname) not specified');

        $this->target->createFromOptions([
            'host' => ['localhost'],
            'port' => ['5432'],
            'username' => ['postgres'],
        ]);
    }

    public function testBuildFromOptionsMissingUsername(): void
    {
        $this->expectException(KeeperException::class);
        $this->expectExceptionMessage('Required option -U (--username) not specified');

        $this->target->createFromOptions([
            'host' => ['localhost'],
            'port' => ['5432'],
            'dbname' => ['mydb'],
        ]);
    }
}
