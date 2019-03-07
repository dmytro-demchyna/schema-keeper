<?php

namespace SchemaKeeper\Tests\Provider\PostgreSQL;

use SchemaKeeper\Provider\PostgreSQL\PSQLClient;
use SchemaKeeper\Tests\SchemaTestCase;

class PSQLClientTest extends SchemaTestCase
{
    /**
     * @var PSQLClient
     */
    private $target;

    public function setUp()
    {
        parent::setUp();

        $dbParams = $this->getDbParams();

        $this->target = new PSQLClient(
            $dbParams->getDbName(),
            $dbParams->getHost(),
            $dbParams->getPort(),
            $dbParams->getUser(),
            $dbParams->getPassword()
        );
    }

    public function testOneCommandOk()
    {
        $expected = "12345";

        $actual = $this->target->run("\qecho -n 12345");

        self::assertEquals($expected, $actual);
    }

    public function testMultipleCommandOk()
    {
        $expected = [
            "12345",
            "54321"
        ];

        $actual = $this->target->runMultiple(["\qecho -n 12345", "\qecho -n 54321"]);

        self::assertEquals($expected, $actual);
    }

    public function testEmptyCommands()
    {
        $expected = [];

        $actual = $this->target->runMultiple([]);

        self::assertEquals($expected, $actual);
    }
}
